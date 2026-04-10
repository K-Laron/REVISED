<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Request;
use App\Helpers\IdGenerator;
use App\Models\Animal;
use App\Models\AnimalPhoto;
use App\Models\Breed;
use App\Models\Kennel;
use App\Services\Animal\AnimalKennelCoordinator;
use App\Services\Animal\AnimalPayloadFactory;
use App\Services\Animal\AnimalPhotoManager;
use App\Support\MediaPath;
use InvalidArgumentException;
use RuntimeException;

class AnimalService
{
    public function __construct(
        private readonly Animal $animals,
        private readonly Breed $breeds,
        private readonly AnimalPhoto $photos,
        private readonly QrCodeService $qrCodes,
        private readonly AuditService $audit,
        private readonly AnimalPayloadFactory $payloads,
        private readonly AnimalPhotoManager $photoManager,
        private readonly AnimalKennelCoordinator $animalKennels,
        private readonly Kennel $kennels
    ) {
    }

    public function list(array $filters, int $page, int $perPage): array
    {
        $this->animals->reconcileCompletedAdoptions();
        $result = $this->animals->paginate($filters, $page, $perPage);

        foreach ($result['items'] as &$animal) {
            $animal['primary_photo_path'] = MediaPath::normalizePublicImagePath($animal['primary_photo_path'] ?? null);
        }
        unset($animal);

        return $result;
    }

    public function breeds(?string $species = null): array
    {
        return $this->breeds->list($species);
    }

    public function availableKennels(?int $includeKennelId = null): array
    {
        return $this->kennels->listAvailableForSelection($includeKennelId);
    }

    public function create(array $data, array $files, int $userId, Request $request): array
    {
        Database::beginTransaction();

        try {
            $payload = $this->payloads->build($data, $userId);
            $payload['animal_id'] = IdGenerator::next('animal_id');

            $animalId = $this->animals->create($payload);
            $this->animalKennels->syncAssignment($animalId, null, $payload['kennel_id'] ?? null, $userId);
            $this->photoManager->upload($animalId, $files['photos'] ?? null, $userId);
            $qr = $this->qrCodes->generateForAnimal($animalId, $payload['animal_id'], $userId);
            $this->animals->db->commit();

            $animal = $this->get((string) $animalId);
            $this->audit->record($userId, 'create', 'animals', 'animals', $animalId, [], $animal, $request);

            return ['animal' => $animal, 'qr' => $qr];
        } catch (\Throwable $exception) {
            Database::rollBack();
            throw $exception;
        }
    }

    public function update(int $animalId, array $data, int $userId, Request $request): array
    {
        $current = $this->get((string) $animalId);
        Database::beginTransaction();

        try {
            $payload = $this->payloads->build($data, $userId, false);
            $currentKennel = $this->animals->currentKennel($animalId);
            $currentKennelId = $currentKennel['id'] ?? null;

            $this->animals->update($animalId, $payload);
            $this->animalKennels->syncAssignment($animalId, $currentKennelId, $payload['kennel_id'] ?? null, $userId);

            Database::commit();

            $animal = $this->get((string) $animalId);
            $this->audit->record($userId, 'update', 'animals', 'animals', $animalId, $current, $animal, $request);

            return $animal;
        } catch (\Throwable $exception) {
            Database::rollBack();
            throw $exception;
        }
    }

    public function get(string $id, bool $includeDeleted = false): array
    {
        if (ctype_digit($id)) {
            $this->animals->reconcileCompletedAdoptions((int) $id);
        }

        $animal = $this->animals->find($id, $includeDeleted);
        if ($animal === false) {
            throw new RuntimeException('Animal not found.');
        }

        $animal['photos'] = MediaPath::filterValidImageRows($this->photos->listByAnimal((int) $animal['id']));
        $animal['current_kennel'] = $this->animals->currentKennel((int) $animal['id']);
        $animal['kennel_history'] = $this->animals->kennelHistory((int) $animal['id']);
        $animal['medical_records'] = $this->animals->medicalRecords((int) $animal['id']);

        return $animal;
    }

    public function delete(int $animalId, int $userId, Request $request): void
    {
        $current = $this->get((string) $animalId);
        $this->animals->setDeleted($animalId, true, $userId);
        $this->audit->record($userId, 'delete', 'animals', 'animals', $animalId, $current, ['is_deleted' => true], $request);
    }

    public function restore(int $animalId, int $userId, Request $request): void
    {
        $this->animals->setDeleted($animalId, false, null);
        $this->audit->record($userId, 'restore', 'animals', 'animals', $animalId, ['is_deleted' => true], ['is_deleted' => false], $request);
    }

    public function updateStatus(int $animalId, string $status, ?string $reason, int $userId, Request $request): array
    {
        $animal = $this->get((string) $animalId);
        $outcomeStatuses = ['Adopted', 'Deceased', 'Transferred'];
        $this->animals->updateStatus(
            $animalId,
            $status,
            $reason,
            in_array($status, $outcomeStatuses, true) ? date('Y-m-d H:i:s') : null,
            $userId
        );

        $updated = $this->get((string) $animalId);
        $this->audit->record($userId, 'update', 'animals', 'animals', $animalId, ['status' => $animal['status']], ['status' => $status, 'status_reason' => $reason], $request);

        return $updated;
    }

    public function uploadPhoto(int $animalId, mixed $photoInput, int $userId, Request $request): array
    {
        $animal = $this->get((string) $animalId);
        $this->photoManager->upload($animalId, $photoInput, $userId);
        $updated = $this->get((string) $animalId);
        $this->audit->record($userId, 'update', 'animals', 'animal_photos', $animalId, ['photo_count' => count($animal['photos'])], ['photo_count' => count($updated['photos'])], $request);

        return $updated['photos'];
    }

    public function reorderPhotos(int $animalId, array $photoIds, int $userId, Request $request): array
    {
        $animal = $this->get((string) $animalId);
        $currentPhotoIds = array_map('intval', array_column($animal['photos'], 'id'));

        if ($currentPhotoIds === []) {
            throw new InvalidArgumentException('Animal has no photos to reorder.');
        }

        $normalizedPhotoIds = array_values(array_map('intval', $photoIds));
        $expectedPhotoIds = $currentPhotoIds;
        sort($expectedPhotoIds);
        $sortedPhotoIds = $normalizedPhotoIds;
        sort($sortedPhotoIds);

        if ($sortedPhotoIds !== $expectedPhotoIds) {
            throw new InvalidArgumentException('Photo order payload does not match the current animal photos.');
        }

        Database::beginTransaction();

        try {
            foreach ($normalizedPhotoIds as $index => $photoId) {
                $this->photos->updateOrdering($animalId, $photoId, $index, $index === 0 ? 1 : 0);
            }

            Database::commit();
        } catch (\Throwable $exception) {
            Database::rollBack();
            throw $exception;
        }

        $updated = $this->get((string) $animalId);
        $this->audit->record(
            $userId,
            'update',
            'animals',
            'animal_photos',
            $animalId,
            ['photo_ids' => $currentPhotoIds],
            ['photo_ids' => array_map('intval', array_column($updated['photos'], 'id'))],
            $request
        );

        return $updated['photos'];
    }

    public function deletePhoto(int $animalId, int $photoId, int $userId, Request $request): void
    {
        $photo = $this->photos->findByAnimal($animalId, $photoId);
        Database::beginTransaction();

        try {
            $this->photoManager->delete($animalId, $photoId);
            $this->normalizePhotoOrdering($animalId);
            Database::commit();
        } catch (\Throwable $exception) {
            Database::rollBack();
            throw $exception;
        }

        $this->audit->record($userId, 'delete', 'animals', 'animal_photos', $photoId, $photo ?: [], [], $request);
    }

    public function timeline(int $animalId): array
    {
        $animal = $this->get((string) $animalId);
        $entries = [[
            'type' => 'intake',
            'date' => $animal['intake_date'],
            'title' => 'Animal intake recorded',
            'description' => trim(($animal['intake_type'] ?? '') . ' ' . ($animal['location_found'] ? '· ' . $animal['location_found'] : '')),
        ]];

        foreach ($animal['kennel_history'] as $assignment) {
            $entries[] = [
                'type' => 'kennel',
                'date' => $assignment['assigned_at'],
                'title' => 'Assigned to kennel ' . $assignment['kennel_code'],
                'description' => $assignment['zone'] . ' · ' . $assignment['size_category'],
            ];
        }

        foreach ($animal['medical_records'] as $record) {
            $entries[] = [
                'type' => 'medical',
                'date' => $record['record_date'],
                'title' => ucfirst((string) $record['procedure_type']) . ' record added',
                'description' => $record['general_notes'] ?: 'Medical entry recorded.',
            ];
        }

        usort($entries, static fn (array $a, array $b) => strcmp((string) $b['date'], (string) $a['date']));

        return $entries;
    }

    private function normalizePhotoOrdering(int $animalId): void
    {
        $photos = $this->photos->listByAnimal($animalId);

        foreach ($photos as $index => $photo) {
            $this->photos->updateOrdering($animalId, (int) $photo['id'], $index, $index === 0 ? 1 : 0);
        }
    }
}

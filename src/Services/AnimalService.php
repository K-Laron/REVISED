<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Request;
use App\Helpers\IdGenerator;
use App\Models\Animal;
use App\Models\AnimalPhoto;
use App\Models\Breed;
use App\Services\Animal\AnimalKennelCoordinator;
use App\Services\Animal\AnimalPayloadFactory;
use App\Services\Animal\AnimalPhotoManager;
use App\Support\MediaPath;
use RuntimeException;

class AnimalService
{
    private Animal $animals;
    private Breed $breeds;
    private AnimalPhoto $photos;
    private QrCodeService $qrCodes;
    private AuditService $audit;
    private AnimalPayloadFactory $payloads;
    private AnimalPhotoManager $photoManager;
    private AnimalKennelCoordinator $kennels;

    public function __construct(
        ?Animal $animals = null,
        ?Breed $breeds = null,
        ?AnimalPhoto $photos = null,
        ?QrCodeService $qrCodes = null,
        ?AuditService $audit = null,
        ?AnimalPayloadFactory $payloads = null,
        ?AnimalPhotoManager $photoManager = null,
        ?AnimalKennelCoordinator $kennels = null
    )
    {
        $this->animals = $animals ?? new Animal();
        $this->breeds = $breeds ?? new Breed();
        $this->photos = $photos ?? new AnimalPhoto();
        $this->qrCodes = $qrCodes ?? new QrCodeService();
        $this->audit = $audit ?? new AuditService();
        $this->payloads = $payloads ?? new AnimalPayloadFactory();
        $this->photoManager = $photoManager ?? new AnimalPhotoManager($this->photos);
        $this->kennels = $kennels ?? new AnimalKennelCoordinator($this->animals);
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
        $sql = "SELECT id, kennel_code, zone, size_category, allowed_species, status
                FROM kennels
                WHERE is_deleted = 0 AND (status = 'Available'";
        $bindings = [];

        if ($includeKennelId !== null) {
            $sql .= ' OR id = :id';
            $bindings['id'] = $includeKennelId;
        }

        $sql .= ') ORDER BY zone, kennel_code';

        return Database::fetchAll($sql, $bindings);
    }

    public function create(array $data, array $files, int $userId, Request $request): array
    {
        Database::beginTransaction();

        try {
            $payload = $this->payloads->build($data, $userId);
            $payload['animal_id'] = IdGenerator::next('animal_id');

            $animalId = $this->animals->create($payload);
            $this->kennels->syncAssignment($animalId, null, $payload['kennel_id'] ?? null, $userId);
            $this->photoManager->upload($animalId, $files['photos'] ?? null, $userId);
            $qr = $this->qrCodes->generateForAnimal($animalId, $payload['animal_id'], $userId);
            Database::commit();

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
            $this->kennels->syncAssignment($animalId, $currentKennelId, $payload['kennel_id'] ?? null, $userId);

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

    public function deletePhoto(int $animalId, int $photoId, int $userId, Request $request): void
    {
        $photo = $this->photos->find($animalId, $photoId);
        $this->photoManager->delete($animalId, $photoId);
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
}

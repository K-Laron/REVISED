<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Request;
use App\Helpers\IdGenerator;
use App\Helpers\Sanitizer;
use App\Models\Animal;
use App\Models\AnimalPhoto;
use App\Models\Breed;
use App\Support\MediaPath;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use RuntimeException;

class AnimalService
{
    private Animal $animals;
    private Breed $breeds;
    private AnimalPhoto $photos;
    private QrCodeService $qrCodes;
    private AuditService $audit;

    public function __construct(
        ?Animal $animals = null,
        ?Breed $breeds = null,
        ?AnimalPhoto $photos = null,
        ?QrCodeService $qrCodes = null,
        ?AuditService $audit = null
    )
    {
        $this->animals = $animals ?? new Animal();
        $this->breeds = $breeds ?? new Breed();
        $this->photos = $photos ?? new AnimalPhoto();
        $this->qrCodes = $qrCodes ?? new QrCodeService();
        $this->audit = $audit ?? new AuditService();
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
            $payload = $this->normalizeAnimalPayload($data, $userId);
            $payload['animal_id'] = IdGenerator::next('animal_id');

            if (($payload['kennel_id'] ?? null) !== null) {
                $this->assertKennelAvailable((int) $payload['kennel_id']);
            }

            $animalId = $this->animals->create($payload);

            if (($payload['kennel_id'] ?? null) !== null) {
                $this->animals->assignKennel($animalId, $payload['kennel_id'], $userId);
            }

            $this->storeUploadedPhotos($animalId, $files['photos'] ?? null, $userId);
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
            $payload = $this->normalizeAnimalPayload($data, $userId, false);
            $currentKennel = $this->animals->currentKennel($animalId);
            $currentKennelId = $currentKennel['id'] ?? null;

            if (($payload['kennel_id'] ?? null) !== null && (string) $payload['kennel_id'] !== (string) $currentKennelId) {
                $this->assertKennelAvailable((int) $payload['kennel_id']);
            }

            $this->animals->update($animalId, $payload);

            $newKennelId = $payload['kennel_id'] ?? null;

            if ((string) ($newKennelId ?? '') !== (string) ($currentKennelId ?? '')) {
                $this->animals->releaseKennelOccupancy($animalId, $userId);
                if ($newKennelId !== null) {
                    $this->animals->assignKennel($animalId, $newKennelId, $userId);
                }
            }

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
        $this->storeUploadedPhotos($animalId, $photoInput, $userId);
        $updated = $this->get((string) $animalId);
        $this->audit->record($userId, 'update', 'animals', 'animal_photos', $animalId, ['photo_count' => count($animal['photos'])], ['photo_count' => count($updated['photos'])], $request);

        return $updated['photos'];
    }

    public function deletePhoto(int $animalId, int $photoId, int $userId, Request $request): void
    {
        $photo = $this->photos->find($animalId, $photoId);
        if ($photo === false) {
            throw new RuntimeException('Photo not found.');
        }

        $absolutePath = dirname(__DIR__, 2) . '/public/' . $photo['file_path'];
        if (is_file($absolutePath)) {
            unlink($absolutePath);
        }

        $this->photos->delete($photoId);
        $this->audit->record($userId, 'delete', 'animals', 'animal_photos', $photoId, $photo, [], $request);
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

    private function normalizeAnimalPayload(array $data, int $userId, bool $creating = true): array
    {
        $intakeType = (string) ($data['intake_type'] ?? '');
        $showLocationFound = $intakeType === 'Stray';
        $showSurrenderReason = $intakeType === 'Owner Surrender';
        $showBroughtBy = in_array($intakeType, ['Owner Surrender', 'Confiscated', 'Transfer'], true);
        $showAuthority = in_array($intakeType, ['Stray', 'Confiscated'], true);

        return [
            'animal_id' => $data['animal_id'] ?? null,
            'name' => ($data['name'] ?? '') !== '' ? $data['name'] : null,
            'species' => (string) ($data['species'] ?? ''),
            'breed_id' => ($data['breed_id'] ?? '') !== '' ? (int) $data['breed_id'] : null,
            'breed_other' => ($data['breed_other'] ?? '') !== '' ? $data['breed_other'] : null,
            'gender' => (string) ($data['gender'] ?? ''),
            'age_years' => ($data['age_years'] ?? '') !== '' ? (int) $data['age_years'] : null,
            'age_months' => ($data['age_months'] ?? '') !== '' ? (int) $data['age_months'] : null,
            'color_markings' => ($data['color_markings'] ?? '') !== '' ? $data['color_markings'] : null,
            'size' => (string) ($data['size'] ?? ''),
            'weight_kg' => ($data['weight_kg'] ?? '') !== '' ? round((float) $data['weight_kg'], 2) : null,
            'distinguishing_features' => ($data['distinguishing_features'] ?? '') !== '' ? $data['distinguishing_features'] : null,
            'special_needs_notes' => ($data['special_needs_notes'] ?? '') !== '' ? $data['special_needs_notes'] : null,
            'microchip_number' => ($data['microchip_number'] ?? '') !== '' ? $data['microchip_number'] : null,
            'spay_neuter_status' => ($data['spay_neuter_status'] ?? '') !== '' ? $data['spay_neuter_status'] : 'Unknown',
            'intake_type' => $intakeType,
            'intake_date' => str_contains((string) ($data['intake_date'] ?? ''), 'T')
                ? str_replace('T', ' ', (string) $data['intake_date']) . ':00'
                : (string) ($data['intake_date'] ?? ''),
            'location_found' => $showLocationFound && ($data['location_found'] ?? '') !== '' ? $data['location_found'] : null,
            'barangay_of_origin' => ($data['barangay_of_origin'] ?? '') !== '' ? $data['barangay_of_origin'] : null,
            'impoundment_order_number' => $showAuthority && ($data['impoundment_order_number'] ?? '') !== '' ? $data['impoundment_order_number'] : null,
            'authority_name' => $showAuthority && ($data['authority_name'] ?? '') !== '' ? $data['authority_name'] : null,
            'authority_position' => $showAuthority && ($data['authority_position'] ?? '') !== '' ? $data['authority_position'] : null,
            'authority_contact' => $showAuthority ? Sanitizer::phone($data['authority_contact'] ?? null) : null,
            'brought_by_name' => $showBroughtBy && ($data['brought_by_name'] ?? '') !== '' ? $data['brought_by_name'] : null,
            'brought_by_contact' => $showBroughtBy ? Sanitizer::phone($data['brought_by_contact'] ?? null) : null,
            'brought_by_address' => $showBroughtBy && ($data['brought_by_address'] ?? '') !== '' ? $data['brought_by_address'] : null,
            'impounding_officer_name' => ($data['impounding_officer_name'] ?? '') !== '' ? $data['impounding_officer_name'] : null,
            'surrender_reason' => $showSurrenderReason && ($data['surrender_reason'] ?? '') !== '' ? $data['surrender_reason'] : null,
            'condition_at_intake' => (string) ($data['condition_at_intake'] ?? ''),
            'vaccination_status_at_intake' => ($data['vaccination_status_at_intake'] ?? '') !== '' ? $data['vaccination_status_at_intake'] : 'Unknown',
            'temperament' => ($data['temperament'] ?? '') !== '' ? $data['temperament'] : 'Unknown',
            'status' => $creating ? 'Available' : ($data['status'] ?? 'Available'),
            'status_reason' => $creating ? 'Initial intake' : ($data['status_reason'] ?? null),
            'status_changed_at' => $creating ? date('Y-m-d H:i:s') : ($data['status_changed_at'] ?? null),
            'created_by' => $creating ? $userId : null,
            'updated_by' => $userId,
            'kennel_id' => ($data['kennel_id'] ?? '') !== '' ? (int) $data['kennel_id'] : null,
        ];
    }

    private function storeUploadedPhotos(int $animalId, mixed $photoInput, int $userId): void
    {
        $files = $this->normalizeFiles($photoInput);
        if ($files === []) {
            return;
        }

        $directory = dirname(__DIR__, 2) . '/public/uploads/animals/' . $animalId;
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $existingCount = count($this->photos->listByAnimal($animalId));
        $canOptimize = extension_loaded('gd');
        $manager = $canOptimize ? new ImageManager(new Driver()) : null;

        foreach ($files as $index => $file) {
            if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                continue;
            }

            if (!is_uploaded_file((string) $file['tmp_name']) && !is_file((string) $file['tmp_name'])) {
                continue;
            }

            $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
            $normalizedExtension = $extension === 'jpeg' ? 'jpg' : $extension;
            $storedExtension = $canOptimize ? 'jpg' : $normalizedExtension;
            $fileName = $this->generatePhotoFileName($directory, $storedExtension);
            $relativePath = 'uploads/animals/' . $animalId . '/' . $fileName;
            $absolutePath = dirname(__DIR__, 2) . '/public/' . $relativePath;
            $mimeType = (string) ($file['type'] ?? 'application/octet-stream');

            if ($canOptimize && $manager !== null) {
                $image = $manager->read((string) $file['tmp_name']);
                $image->scaleDown(width: 1600, height: 1600);
                $image->toJpeg(85)->save($absolutePath);
                $mimeType = 'image/jpeg';
            } else {
                $this->movePhotoToStorage((string) $file['tmp_name'], $absolutePath);
                $mimeType = $this->detectMimeType($absolutePath, $mimeType);
            }

            $this->photos->create([
                'animal_id' => $animalId,
                'file_path' => $relativePath,
                'file_name' => $fileName,
                'file_size_bytes' => filesize($absolutePath) ?: 0,
                'mime_type' => $mimeType,
                'is_primary' => $existingCount === 0 && $index === 0 ? 1 : 0,
                'sort_order' => $existingCount + $index,
                'uploaded_by' => $userId,
            ]);
        }
    }

    private function generatePhotoFileName(string $directory, string $extension): string
    {
        do {
            $fileName = 'animal-photo-' . bin2hex(random_bytes(16)) . '.' . $extension;
        } while (is_file($directory . '/' . $fileName));

        return $fileName;
    }

    private function normalizeFiles(mixed $input): array
    {
        if (!is_array($input) || !isset($input['name'])) {
            return [];
        }

        if (!is_array($input['name'])) {
            return [$input];
        }

        $files = [];
        foreach ($input['name'] as $index => $name) {
            $files[] = [
                'name' => $name,
                'type' => $input['type'][$index] ?? null,
                'tmp_name' => $input['tmp_name'][$index] ?? null,
                'error' => $input['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                'size' => $input['size'][$index] ?? 0,
            ];
        }

        return $files;
    }

    private function assertKennelAvailable(int $kennelId): void
    {
        $kennel = Database::fetch(
            "SELECT id, status FROM kennels WHERE id = :id AND is_deleted = 0 LIMIT 1",
            ['id' => $kennelId]
        );

        if ($kennel === false) {
            throw new RuntimeException('Selected kennel was not found.');
        }

        if ($kennel['status'] !== 'Available') {
            throw new RuntimeException('Selected kennel is not available.');
        }
    }

    private function movePhotoToStorage(string $source, string $destination): void
    {
        $moved = is_uploaded_file($source)
            ? move_uploaded_file($source, $destination)
            : copy($source, $destination);

        if (!$moved) {
            throw new RuntimeException('Failed to store uploaded photo.');
        }
    }

    private function detectMimeType(string $path, string $fallback): string
    {
        if (!is_file($path)) {
            return $fallback;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            return $fallback;
        }

        $mimeType = finfo_file($finfo, $path) ?: $fallback;
        finfo_close($finfo);

        return $mimeType;
    }
}

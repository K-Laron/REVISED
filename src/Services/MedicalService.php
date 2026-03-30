<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Request;
use App\Models\Animal;
use App\Models\DewormingRecord;
use App\Models\EuthanasiaRecord;
use App\Models\ExaminationRecord;
use App\Models\MedicalLabResult;
use App\Models\MedicalPrescription;
use App\Models\MedicalRecord;
use App\Models\SurgeryRecord;
use App\Models\TreatmentRecord;
use App\Models\VaccinationRecord;
use App\Models\VitalSign;
use App\Services\Medical\MedicalPayloadFactory;
use App\Services\Medical\MedicalProcedureConfig;
use App\Services\Medical\TreatmentInventorySynchronizer;
use App\Support\InputNormalizer;
use App\Support\MediaPath;
use RuntimeException;

class MedicalService
{
    private MedicalRecord $records;
    private VaccinationRecord $vaccinations;
    private SurgeryRecord $surgeries;
    private ExaminationRecord $examinations;
    private TreatmentRecord $treatments;
    private DewormingRecord $dewormings;
    private EuthanasiaRecord $euthanasias;
    private Animal $animals;
    private VitalSign $vitalSigns;
    private MedicalPrescription $prescriptions;
    private MedicalLabResult $labResults;
    private AuditService $audit;
    private MedicalProcedureConfig $procedureConfig;
    private MedicalPayloadFactory $payloadFactory;
    private TreatmentInventorySynchronizer $treatmentInventory;

    public function __construct()
    {
        $this->records = new MedicalRecord();
        $this->vaccinations = new VaccinationRecord();
        $this->surgeries = new SurgeryRecord();
        $this->examinations = new ExaminationRecord();
        $this->treatments = new TreatmentRecord();
        $this->dewormings = new DewormingRecord();
        $this->euthanasias = new EuthanasiaRecord();
        $this->animals = new Animal();
        $this->vitalSigns = new VitalSign();
        $this->prescriptions = new MedicalPrescription();
        $this->labResults = new MedicalLabResult();
        $this->audit = new AuditService();
        $this->procedureConfig = new MedicalProcedureConfig();
        $this->payloadFactory = new MedicalPayloadFactory($this->treatments);
        $this->treatmentInventory = new TreatmentInventorySynchronizer(new \App\Models\InventoryItem(), new \App\Models\StockTransaction());
    }

    public function list(array $filters, int $page, int $perPage): array
    {
        return $this->records->paginate($filters, $page, $perPage);
    }

    public function get(int $id): array
    {
        $record = $this->records->find($id);
        if ($record === false) {
            throw new RuntimeException('Medical record not found.');
        }

        $record['details'] = $this->subtypeRecord((int) $record['id'], (string) $record['procedure_type']);
        $record['vital_signs'] = $this->vitalSigns->findByMedicalRecordId((int) $record['id']) ?: [];
        $record['prescriptions'] = $this->prescriptions->findByMedicalRecordId((int) $record['id']);
        $record['lab_results'] = $this->normalizeLabResults($this->labResults->findByMedicalRecordId((int) $record['id']));

        return $record;
    }

    public function byAnimal(int $animalId): array
    {
        if ($this->animals->find($animalId) === false) {
            throw new RuntimeException('Animal not found.');
        }

        $records = $this->records->listByAnimal($animalId);

        foreach ($records as &$record) {
            $record['details'] = $this->subtypeRecord((int) $record['id'], (string) $record['procedure_type']);
        }
        unset($record);

        return $records;
    }

    public function create(string $type, array $data, int $userId, Request $request): array
    {
        if ($this->animals->find((int) $data['animal_id']) === false) {
            throw new RuntimeException('Animal not found.');
        }

        $attachmentSync = [
            'new_files' => [],
            'obsolete_files' => [],
        ];

        Database::beginTransaction();

        try {
            $basePayload = $this->payloadFactory->basePayload($type, $data, $userId, true);
            $medicalRecordId = $this->records->create($basePayload);
            $detailPayload = $this->payloadFactory->subtypePayload($type, $data, $medicalRecordId, true);

            $this->persistSubtype($type, $medicalRecordId, $detailPayload, true);
            $attachmentSync = $this->saveSharedSections($medicalRecordId, $data, $request->file('lab_attachments'));

            if ($type === 'treatment') {
                $this->treatmentInventory->sync(null, $detailPayload, $userId, $medicalRecordId);
            }

            $this->syncAnimalStatusAfterWrite($type, (int) $data['animal_id'], $detailPayload, $userId);

            Database::commit();
        } catch (\Throwable $exception) {
            Database::rollBack();
            $this->deleteStoredFiles($attachmentSync['new_files']);
            throw $exception;
        }

        $this->deleteStoredFiles($attachmentSync['obsolete_files']);

        $record = $this->get($medicalRecordId);
        $this->audit->record($userId, 'create', 'medical', 'medical_records', $medicalRecordId, [], $record, $request);

        return $record;
    }

    public function update(int $id, array $data, int $userId, Request $request): array
    {
        $current = $this->get($id);
        $type = (string) $current['procedure_type'];
        $existingLabResults = $this->labResults->findByMedicalRecordId($id);
        $attachmentSync = [
            'new_files' => [],
            'obsolete_files' => [],
        ];

        Database::beginTransaction();

        try {
            $basePayload = $this->payloadFactory->basePayload($type, $data + ['animal_id' => $current['animal_id']], $userId, false);
            $this->records->update($id, $basePayload);
            $detailPayload = $this->payloadFactory->subtypePayload($type, $data, $id, false);

            if ($type === 'treatment') {
                $this->treatmentInventory->sync($current['details'], $detailPayload, $userId, $id);
            }

            $this->persistSubtype($type, $id, $detailPayload, false);
            $attachmentSync = $this->saveSharedSections($id, $data, $request->file('lab_attachments'), $existingLabResults);
            $this->syncAnimalStatusAfterWrite($type, (int) $current['animal_id'], $detailPayload, $userId);

            Database::commit();
        } catch (\Throwable $exception) {
            Database::rollBack();
            $this->deleteStoredFiles($attachmentSync['new_files']);
            throw $exception;
        }

        $this->deleteStoredFiles($attachmentSync['obsolete_files']);

        $record = $this->get($id);
        $this->audit->record($userId, 'update', 'medical', 'medical_records', $id, $current, $record, $request);

        return $record;
    }

    public function delete(int $id, int $userId, Request $request): void
    {
        $current = $this->get($id);

        Database::beginTransaction();

        try {
            if ((string) $current['procedure_type'] === 'treatment') {
                $this->treatmentInventory->restore($current['details'], $userId, $id);
            }

            $this->records->setDeleted($id, true);
            Database::commit();
        } catch (\Throwable $exception) {
            Database::rollBack();
            throw $exception;
        }

        $this->audit->record($userId, 'delete', 'medical', 'medical_records', $id, $current, ['is_deleted' => true], $request);
    }

    public function dueVaccinations(): array
    {
        return $this->records->dueVaccinations();
    }

    public function dueDewormings(): array
    {
        return $this->records->dueDewormings();
    }

    public function practitioners(): array
    {
        return Database::fetchAll(
            'SELECT u.id, u.email, u.first_name, u.last_name, r.name AS role_name, r.display_name AS role_display_name
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             WHERE u.is_deleted = 0
               AND u.is_active = 1
             ORDER BY CASE WHEN r.name = "veterinarian" THEN 0 ELSE 1 END, u.first_name ASC, u.last_name ASC'
        );
    }

    public function animalOptions(): array
    {
        return Database::fetchAll(
            'SELECT id, animal_id, name, species, status
             FROM animals
             WHERE is_deleted = 0
             ORDER BY created_at DESC, id DESC'
        );
    }

    public function treatmentInventoryOptions(): array
    {
        return Database::fetchAll(
            'SELECT ii.id, ii.sku, ii.name, ii.quantity_on_hand, ii.unit_of_measure, ic.name AS category_name
             FROM inventory_items ii
             INNER JOIN inventory_categories ic ON ic.id = ii.category_id
             WHERE ii.is_deleted = 0
               AND ii.is_active = 1
             ORDER BY ic.name ASC, ii.name ASC'
        );
    }

    public function formConfig(string $type): array
    {
        return $this->procedureConfig->forType($type);
    }

    private function saveSharedSections(int $medicalRecordId, array $data, mixed $labAttachmentInput = null, array $existingLabResults = []): array
    {
        $attachmentSync = [
            'new_files' => [],
            'obsolete_files' => [],
        ];

        // Vital signs
        $hasVitalData = false;
        $vitalFields = ['vs_weight_kg', 'vs_temperature_celsius', 'vs_heart_rate_bpm', 'vs_respiratory_rate', 'vs_body_condition_score'];
        foreach ($vitalFields as $field) {
            if (($data[$field] ?? '') !== '') {
                $hasVitalData = true;
                break;
            }
        }
        if ($hasVitalData) {
            $this->vitalSigns->upsert($medicalRecordId, [
                'weight_kg' => InputNormalizer::decimalOrNull($data['vs_weight_kg'] ?? null),
                'temperature_celsius' => InputNormalizer::decimalOrNull($data['vs_temperature_celsius'] ?? null, 1),
                'heart_rate_bpm' => InputNormalizer::intOrNull($data['vs_heart_rate_bpm'] ?? null),
                'respiratory_rate' => InputNormalizer::intOrNull($data['vs_respiratory_rate'] ?? null),
                'body_condition_score' => InputNormalizer::intOrNull($data['vs_body_condition_score'] ?? null),
            ]);
        }

        // Prescriptions
        $prescriptionsRaw = $data['prescriptions'] ?? [];
        if (is_string($prescriptionsRaw)) {
            $prescriptionsRaw = json_decode($prescriptionsRaw, true) ?: [];
        }
        if (is_array($prescriptionsRaw)) {
            $this->prescriptions->bulkReplaceForRecord($medicalRecordId, $prescriptionsRaw);
        }

        // Lab results
        $labResultsRaw = $data['lab_results'] ?? [];
        if (is_string($labResultsRaw)) {
            $labResultsRaw = json_decode($labResultsRaw, true) ?: [];
        }
        if (is_array($labResultsRaw)) {
            $labResultsRaw = $this->attachUploadedLabImages($medicalRecordId, $labResultsRaw, $labAttachmentInput, $attachmentSync);
            $this->labResults->bulkReplaceForRecord($medicalRecordId, $labResultsRaw);
            $attachmentSync['obsolete_files'] = $this->diffAttachmentPaths(
                $this->extractAttachmentPaths($existingLabResults),
                $this->extractAttachmentPaths($labResultsRaw)
            );
        }

        return $attachmentSync;
    }

    private function subtypeRecord(int $medicalRecordId, string $type): array
    {
        return match ($type) {
            'vaccination' => $this->vaccinations->findByMedicalRecord($medicalRecordId) ?: [],
            'surgery' => $this->surgeries->findByMedicalRecord($medicalRecordId) ?: [],
            'examination' => $this->examinations->findByMedicalRecord($medicalRecordId) ?: [],
            'treatment' => $this->treatments->findByMedicalRecord($medicalRecordId) ?: [],
            'deworming' => $this->dewormings->findByMedicalRecord($medicalRecordId) ?: [],
            'euthanasia' => $this->euthanasias->findByMedicalRecord($medicalRecordId) ?: [],
            default => [],
        };
    }

    private function persistSubtype(string $type, int $medicalRecordId, array $payload, bool $creating): void
    {
        if ($creating) {
            match ($type) {
                'vaccination' => $this->vaccinations->create($payload),
                'surgery' => $this->surgeries->create($payload),
                'examination' => $this->examinations->create($payload),
                'treatment' => $this->treatments->create($payload),
                'deworming' => $this->dewormings->create($payload),
                'euthanasia' => $this->euthanasias->create($payload),
                default => throw new RuntimeException('Unsupported medical procedure type.'),
            };

            return;
        }

        match ($type) {
            'vaccination' => $this->vaccinations->updateByMedicalRecord($medicalRecordId, $payload),
            'surgery' => $this->surgeries->updateByMedicalRecord($medicalRecordId, $payload),
            'examination' => $this->examinations->updateByMedicalRecord($medicalRecordId, $payload),
            'treatment' => $this->treatments->updateByMedicalRecord($medicalRecordId, $payload),
            'deworming' => $this->dewormings->updateByMedicalRecord($medicalRecordId, $payload),
            'euthanasia' => $this->euthanasias->updateByMedicalRecord($medicalRecordId, $payload),
            default => throw new RuntimeException('Unsupported medical procedure type.'),
        };
    }

    private function syncAnimalStatusAfterWrite(string $type, int $animalId, array $detailPayload, int $userId): void
    {
        $animal = $this->animals->find($animalId);
        if ($animal === false) {
            return;
        }

        if ($type === 'euthanasia') {
            $this->animals->updateStatus(
                $animalId,
                'Deceased',
                'Euthanasia record added.',
                (string) $detailPayload['time_of_death'],
                $userId
            );

            return;
        }

        if (in_array($type, ['surgery', 'examination', 'treatment'], true) && !in_array((string) $animal['status'], ['Deceased', 'Adopted', 'Transferred'], true)) {
            $this->animals->updateStatus(
                $animalId,
                'Under Medical Care',
                ucfirst($type) . ' record added.',
                null,
                $userId
            );
        }
    }

    private function attachUploadedLabImages(int $medicalRecordId, array $labResults, mixed $labAttachmentInput, array &$attachmentSync): array
    {
        $files = $this->normalizeFiles($labAttachmentInput);
        if ($files === []) {
            return $labResults;
        }

        foreach ($labResults as $index => &$labResult) {
            if (trim((string) ($labResult['test_name'] ?? '')) === '') {
                unset($labResult['attachment_index']);
                continue;
            }

            $attachmentIndex = isset($labResult['attachment_index']) ? (int) $labResult['attachment_index'] : null;
            unset($labResult['attachment_index']);

            if ($attachmentIndex === null || !isset($files[$attachmentIndex])) {
                continue;
            }

            $storedPath = $this->storeLabAttachment($medicalRecordId, $files[$attachmentIndex]);
            if ($storedPath !== null) {
                $labResult['attachment_path'] = $storedPath;
                $attachmentSync['new_files'][] = $storedPath;
            }
        }
        unset($labResult);

        return $labResults;
    }

    private function storeLabAttachment(int $medicalRecordId, array $file): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        $source = (string) ($file['tmp_name'] ?? '');
        if (!is_uploaded_file($source) && !is_file($source)) {
            return null;
        }

        $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        $extension = $extension === 'jpeg' ? 'jpg' : $extension;
        $directory = dirname(__DIR__, 2) . '/public/uploads/medical/lab-results/' . $medicalRecordId;

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('Failed to prepare medical attachment storage.');
        }

        do {
            $fileName = 'lab-result-' . bin2hex(random_bytes(12)) . '.' . $extension;
            $absolutePath = $directory . '/' . $fileName;
        } while (is_file($absolutePath));

        $moved = is_uploaded_file($source)
            ? move_uploaded_file($source, $absolutePath)
            : copy($source, $absolutePath);

        if (!$moved) {
            throw new RuntimeException('Failed to store the uploaded medical attachment.');
        }

        return 'uploads/medical/lab-results/' . $medicalRecordId . '/' . $fileName;
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
            $files[(int) $index] = [
                'name' => $name,
                'type' => $input['type'][$index] ?? null,
                'tmp_name' => $input['tmp_name'][$index] ?? null,
                'error' => $input['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                'size' => $input['size'][$index] ?? 0,
            ];
        }

        return $files;
    }

    private function normalizeLabResults(array $rows): array
    {
        foreach ($rows as &$row) {
            $row['attachment_path'] = MediaPath::normalizePublicImagePath($row['attachment_path'] ?? null);
        }
        unset($row);

        return $rows;
    }

    private function extractAttachmentPaths(array $rows): array
    {
        $paths = [];

        foreach ($rows as $row) {
            $path = $this->normalizeRelativeMediaPath($row['attachment_path'] ?? null);
            if ($path === null) {
                continue;
            }

            $paths[] = $path;
        }

        return array_values(array_unique($paths));
    }

    private function diffAttachmentPaths(array $existingPaths, array $nextPaths): array
    {
        $nextLookup = array_fill_keys($nextPaths, true);

        return array_values(array_filter($existingPaths, static fn (string $path): bool => !isset($nextLookup[$path])));
    }

    private function deleteStoredFiles(array $paths): void
    {
        foreach (array_values(array_unique($paths)) as $path) {
            $normalizedPath = $this->normalizeRelativeMediaPath($path);
            if ($normalizedPath === null) {
                continue;
            }

            $absolutePath = dirname(__DIR__, 2) . '/public/' . str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $normalizedPath);
            if (is_file($absolutePath)) {
                @unlink($absolutePath);
            }
        }
    }

    private function normalizeRelativeMediaPath(?string $path): ?string
    {
        $normalizedPath = ltrim(str_replace('\\', '/', trim((string) $path)), '/');

        if ($normalizedPath === '' || str_contains($normalizedPath, '..')) {
            return null;
        }

        return $normalizedPath;
    }
}

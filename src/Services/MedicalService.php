<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Request;
use App\Models\Animal;
use App\Models\DewormingRecord;
use App\Models\EuthanasiaRecord;
use App\Models\ExaminationRecord;
use App\Models\InventoryItem;
use App\Models\MedicalRecord;
use App\Models\StockTransaction;
use App\Models\SurgeryRecord;
use App\Models\TreatmentRecord;
use App\Models\VaccinationRecord;
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
    private InventoryItem $inventoryItems;
    private StockTransaction $stockTransactions;
    private AuditService $audit;

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
        $this->inventoryItems = new InventoryItem();
        $this->stockTransactions = new StockTransaction();
        $this->audit = new AuditService();
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

        Database::beginTransaction();

        try {
            $basePayload = $this->normalizeBasePayload($type, $data, $userId, true);
            $medicalRecordId = $this->records->create($basePayload);
            $detailPayload = $this->normalizeSubtypePayload($type, $data, $medicalRecordId, true);

            $this->persistSubtype($type, $medicalRecordId, $detailPayload, true);

            if ($type === 'treatment') {
                $this->syncTreatmentInventory(null, $detailPayload, $userId, $medicalRecordId);
            }

            $this->syncAnimalStatusAfterWrite($type, (int) $data['animal_id'], $detailPayload, $userId);

            Database::commit();
        } catch (\Throwable $exception) {
            Database::rollBack();
            throw $exception;
        }

        $record = $this->get($medicalRecordId);
        $this->audit->record($userId, 'create', 'medical', 'medical_records', $medicalRecordId, [], $record, $request);

        return $record;
    }

    public function update(int $id, array $data, int $userId, Request $request): array
    {
        $current = $this->get($id);
        $type = (string) $current['procedure_type'];

        Database::beginTransaction();

        try {
            $basePayload = $this->normalizeBasePayload($type, $data + ['animal_id' => $current['animal_id']], $userId, false);
            $this->records->update($id, $basePayload);
            $detailPayload = $this->normalizeSubtypePayload($type, $data, $id, false);

            if ($type === 'treatment') {
                $this->syncTreatmentInventory($current['details'], $detailPayload, $userId, $id);
            }

            $this->persistSubtype($type, $id, $detailPayload, false);
            $this->syncAnimalStatusAfterWrite($type, (int) $current['animal_id'], $detailPayload, $userId);

            Database::commit();
        } catch (\Throwable $exception) {
            Database::rollBack();
            throw $exception;
        }

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
                $this->restoreTreatmentInventory($current['details'], $userId, $id);
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
        $configs = [
            'vaccination' => [
                'label' => 'Vaccination',
                'endpoint' => '/api/medical/vaccination',
                'default_due_days' => 365,
                'fields' => ['vaccine_name', 'vaccine_brand', 'batch_lot_number', 'dosage_ml', 'route', 'injection_site', 'dose_number', 'next_due_date', 'adverse_reactions'],
            ],
            'surgery' => [
                'label' => 'Surgery',
                'endpoint' => '/api/medical/surgery',
                'fields' => ['surgery_type', 'pre_op_weight_kg', 'anesthesia_type', 'anesthesia_drug', 'anesthesia_dosage', 'duration_minutes', 'surgical_notes', 'complications', 'post_op_instructions', 'follow_up_date'],
            ],
            'examination' => [
                'label' => 'Examination',
                'endpoint' => '/api/medical/examination',
                'fields' => ['weight_kg', 'temperature_celsius', 'heart_rate_bpm', 'respiratory_rate', 'body_condition_score', 'overall_assessment', 'recommendations'],
            ],
            'treatment' => [
                'label' => 'Treatment',
                'endpoint' => '/api/medical/treatment',
                'fields' => ['diagnosis', 'medication_name', 'dosage', 'route', 'frequency', 'duration_days', 'start_date', 'end_date', 'quantity_dispensed', 'inventory_item_id', 'special_instructions'],
            ],
            'deworming' => [
                'label' => 'Deworming',
                'endpoint' => '/api/medical/deworming',
                'default_due_days' => 90,
                'fields' => ['dewormer_name', 'brand', 'dosage', 'weight_at_treatment_kg', 'next_due_date'],
            ],
            'euthanasia' => [
                'label' => 'Euthanasia',
                'endpoint' => '/api/medical/euthanasia',
                'fields' => ['reason_category', 'reason_details', 'authorized_by', 'method', 'drug_used', 'drug_dosage', 'time_of_death', 'disposal_method'],
            ],
        ];

        if (!isset($configs[$type])) {
            throw new RuntimeException('Unsupported medical procedure type.');
        }

        return $configs[$type];
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

    private function normalizeBasePayload(string $type, array $data, int $userId, bool $creating): array
    {
        return [
            'animal_id' => (int) $data['animal_id'],
            'procedure_type' => $type,
            'record_date' => (string) $this->normalizeDateTime($data['record_date']),
            'general_notes' => $this->nullIfBlank($data['general_notes'] ?? null),
            'veterinarian_id' => (int) $data['veterinarian_id'],
            'created_by' => $creating ? $userId : null,
            'updated_by' => $userId,
        ];
    }

    private function normalizeSubtypePayload(string $type, array $data, int $medicalRecordId, bool $creating): array
    {
        $payload = match ($type) {
            'vaccination' => [
                'medical_record_id' => $medicalRecordId,
                'vaccine_name' => trim((string) $data['vaccine_name']),
                'vaccine_brand' => $this->nullIfBlank($data['vaccine_brand'] ?? null),
                'batch_lot_number' => $this->nullIfBlank($data['batch_lot_number'] ?? null),
                'dosage_ml' => round((float) $data['dosage_ml'], 2),
                'route' => (string) $data['route'],
                'injection_site' => $this->nullIfBlank($data['injection_site'] ?? null),
                'dose_number' => (int) $data['dose_number'],
                'next_due_date' => $this->normalizeDate($data['next_due_date'] ?? null) ?? $this->defaultDueDate($data['record_date'], 365),
                'adverse_reactions' => $this->nullIfBlank($data['adverse_reactions'] ?? null),
            ],
            'surgery' => [
                'medical_record_id' => $medicalRecordId,
                'surgery_type' => (string) $data['surgery_type'],
                'pre_op_weight_kg' => $this->nullableDecimal($data['pre_op_weight_kg'] ?? null),
                'anesthesia_type' => (string) $data['anesthesia_type'],
                'anesthesia_drug' => $this->nullIfBlank($data['anesthesia_drug'] ?? null),
                'anesthesia_dosage' => $this->nullIfBlank($data['anesthesia_dosage'] ?? null),
                'duration_minutes' => $this->nullableInt($data['duration_minutes'] ?? null),
                'surgical_notes' => $this->nullIfBlank($data['surgical_notes'] ?? null),
                'complications' => $this->nullIfBlank($data['complications'] ?? null),
                'post_op_instructions' => $this->nullIfBlank($data['post_op_instructions'] ?? null),
                'follow_up_date' => $this->normalizeDate($data['follow_up_date'] ?? null),
            ],
            'examination' => [
                'medical_record_id' => $medicalRecordId,
                'weight_kg' => $this->nullableDecimal($data['weight_kg'] ?? null),
                'temperature_celsius' => $this->nullableDecimal($data['temperature_celsius'] ?? null, 1),
                'heart_rate_bpm' => $this->nullableInt($data['heart_rate_bpm'] ?? null),
                'respiratory_rate' => $this->nullableInt($data['respiratory_rate'] ?? null),
                'body_condition_score' => $this->nullableInt($data['body_condition_score'] ?? null),
                'eyes_status' => $this->nullIfBlank($data['eyes_status'] ?? null),
                'eyes_notes' => $this->nullIfBlank($data['eyes_notes'] ?? null),
                'ears_status' => $this->nullIfBlank($data['ears_status'] ?? null),
                'ears_notes' => $this->nullIfBlank($data['ears_notes'] ?? null),
                'teeth_gums_status' => $this->nullIfBlank($data['teeth_gums_status'] ?? null),
                'teeth_gums_notes' => $this->nullIfBlank($data['teeth_gums_notes'] ?? null),
                'skin_coat_status' => $this->nullIfBlank($data['skin_coat_status'] ?? null),
                'skin_coat_notes' => $this->nullIfBlank($data['skin_coat_notes'] ?? null),
                'musculoskeletal_status' => $this->nullIfBlank($data['musculoskeletal_status'] ?? null),
                'musculoskeletal_notes' => $this->nullIfBlank($data['musculoskeletal_notes'] ?? null),
                'overall_assessment' => $this->nullIfBlank($data['overall_assessment'] ?? null),
                'recommendations' => $this->nullIfBlank($data['recommendations'] ?? null),
            ],
            'treatment' => [
                'medical_record_id' => $medicalRecordId,
                'diagnosis' => trim((string) $data['diagnosis']),
                'medication_name' => trim((string) $data['medication_name']),
                'dosage' => trim((string) $data['dosage']),
                'route' => (string) $data['route'],
                'frequency' => trim((string) $data['frequency']),
                'duration_days' => $this->nullableInt($data['duration_days'] ?? null),
                'start_date' => (string) $this->normalizeDate($data['start_date']),
                'end_date' => $this->normalizeDate($data['end_date'] ?? null),
                'quantity_dispensed' => $this->nullableInt($data['quantity_dispensed'] ?? null),
                'inventory_item_id' => $this->nullableInt($data['inventory_item_id'] ?? null),
                'special_instructions' => $this->nullIfBlank($data['special_instructions'] ?? null),
            ],
            'deworming' => [
                'medical_record_id' => $medicalRecordId,
                'dewormer_name' => trim((string) $data['dewormer_name']),
                'brand' => $this->nullIfBlank($data['brand'] ?? null),
                'dosage' => trim((string) $data['dosage']),
                'weight_at_treatment_kg' => $this->nullableDecimal($data['weight_at_treatment_kg'] ?? null),
                'next_due_date' => $this->normalizeDate($data['next_due_date'] ?? null) ?? $this->defaultDueDate($data['record_date'], 90),
            ],
            'euthanasia' => [
                'medical_record_id' => $medicalRecordId,
                'reason_category' => (string) $data['reason_category'],
                'reason_details' => trim((string) $data['reason_details']),
                'authorized_by' => (int) $data['authorized_by'],
                'method' => trim((string) $data['method']),
                'drug_used' => $this->nullIfBlank($data['drug_used'] ?? null),
                'drug_dosage' => $this->nullIfBlank($data['drug_dosage'] ?? null),
                'time_of_death' => (string) $this->normalizeDateTime($data['time_of_death']),
                'death_confirmed' => filter_var($data['death_confirmed'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
                'disposal_method' => (string) $data['disposal_method'],
            ],
            default => throw new RuntimeException('Unsupported medical procedure type.'),
        };

        if ($type === 'treatment' && ($payload['inventory_item_id'] === null || $payload['quantity_dispensed'] === null) && !$creating) {
            $current = $this->treatments->findByMedicalRecord($medicalRecordId);
            if ($current !== false) {
                $payload['inventory_item_id'] = $payload['inventory_item_id'] ?? (($current['inventory_item_id'] ?? null) !== null ? (int) $current['inventory_item_id'] : null);
                $payload['quantity_dispensed'] = $payload['quantity_dispensed'] ?? (($current['quantity_dispensed'] ?? null) !== null ? (int) $current['quantity_dispensed'] : null);
            }
        }

        return $payload;
    }

    private function syncTreatmentInventory(?array $existing, array $next, int $userId, int $medicalRecordId): void
    {
        $existingItemId = (($existing['inventory_item_id'] ?? null) !== null) ? (int) $existing['inventory_item_id'] : null;
        $nextItemId = (($next['inventory_item_id'] ?? null) !== null) ? (int) $next['inventory_item_id'] : null;
        $existingQty = (int) ($existing['quantity_dispensed'] ?? 0);
        $nextQty = (int) ($next['quantity_dispensed'] ?? 0);

        if ($existingItemId !== null && $existingQty > 0 && $existingItemId !== $nextItemId) {
            $this->adjustInventory($existingItemId, $existingQty, $userId, $medicalRecordId, 'return', 'Treatment inventory reassigned.');
            $existingQty = 0;
        }

        if ($existingItemId !== null && $nextItemId === $existingItemId) {
            $delta = $nextQty - $existingQty;
            if ($delta !== 0) {
                $this->adjustInventory(
                    $nextItemId,
                    -$delta,
                    $userId,
                    $medicalRecordId,
                    $delta > 0 ? 'dispensed' : 'return',
                    $delta > 0 ? 'Additional medication dispensed.' : 'Medication quantity reduced.'
                );
            }

            return;
        }

        if ($nextItemId !== null && $nextQty > 0) {
            $this->adjustInventory($nextItemId, -$nextQty, $userId, $medicalRecordId, 'dispensed', 'Medication dispensed for treatment record.');
        }
    }

    private function restoreTreatmentInventory(array $details, int $userId, int $medicalRecordId): void
    {
        $inventoryItemId = (($details['inventory_item_id'] ?? null) !== null) ? (int) $details['inventory_item_id'] : null;
        $quantity = (int) ($details['quantity_dispensed'] ?? 0);

        if ($inventoryItemId !== null && $quantity > 0) {
            $this->adjustInventory($inventoryItemId, $quantity, $userId, $medicalRecordId, 'return', 'Treatment record deleted; stock restored.');
        }
    }

    private function adjustInventory(int $itemId, int $delta, int $userId, int $medicalRecordId, string $reason, string $notes): void
    {
        $item = $this->inventoryItems->find($itemId);
        if ($item === false) {
            throw new RuntimeException('Linked inventory item not found.');
        }

        $before = (int) $item['quantity_on_hand'];
        $after = $before + $delta;

        if ($after < 0) {
            throw new RuntimeException('Inventory quantity is insufficient for the requested treatment dispense.');
        }

        $this->inventoryItems->updateQuantity($itemId, $after, $userId);
        $this->stockTransactions->create([
            'inventory_item_id' => $itemId,
            'transaction_type' => $delta >= 0 ? 'stock_in' : 'stock_out',
            'quantity' => $delta,
            'quantity_before' => $before,
            'quantity_after' => $after,
            'reason' => $reason,
            'reference_type' => 'medical_record',
            'reference_id' => $medicalRecordId,
            'batch_lot_number' => null,
            'expiry_date' => null,
            'source_supplier' => null,
            'notes' => $notes,
            'transacted_by' => $userId,
        ]);
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

    private function normalizeDateTime(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $value = str_replace('T', ' ', $value);

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
            return $value . ' 00:00:00';
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $value) === 1) {
            return $value . ':00';
        }

        return $value;
    }

    private function normalizeDate(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return substr(str_replace('T', ' ', $value), 0, 10);
    }

    private function defaultDueDate(mixed $recordDate, int $days): string
    {
        $recordAt = $this->normalizeDateTime($recordDate) ?? date('Y-m-d H:i:s');

        return date('Y-m-d', strtotime($recordAt . ' +' . $days . ' days'));
    }

    private function nullableInt(mixed $value): ?int
    {
        $value = trim((string) $value);

        return $value === '' ? null : (int) $value;
    }

    private function nullableDecimal(mixed $value, int $precision = 2): ?float
    {
        $value = trim((string) $value);

        return $value === '' ? null : round((float) $value, $precision);
    }

    private function nullIfBlank(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}

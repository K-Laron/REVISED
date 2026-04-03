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
use App\Services\Medical\MedicalAnimalStatusSynchronizer;
use App\Services\Medical\MedicalAttachmentManager;
use App\Services\Medical\MedicalPayloadFactory;
use App\Services\Medical\MedicalProcedureConfig;
use App\Services\Medical\MedicalSharedSectionPersister;
use App\Services\Medical\MedicalSubtypePersister;
use App\Services\Medical\TreatmentInventorySynchronizer;
use RuntimeException;

class MedicalService
{
    private MedicalRecord $records;
    private Animal $animals;
    private AuditService $audit;
    private MedicalProcedureConfig $procedureConfig;
    private MedicalPayloadFactory $payloadFactory;
    private TreatmentInventorySynchronizer $treatmentInventory;
    private MedicalSubtypePersister $subtypes;
    private MedicalSharedSectionPersister $sharedSections;
    private MedicalAttachmentManager $attachments;
    private MedicalAnimalStatusSynchronizer $animalStatus;

    public function __construct(
        ?MedicalRecord $records = null,
        ?Animal $animals = null,
        ?AuditService $audit = null,
        ?MedicalProcedureConfig $procedureConfig = null,
        ?MedicalPayloadFactory $payloadFactory = null,
        ?TreatmentInventorySynchronizer $treatmentInventory = null,
        ?MedicalSubtypePersister $subtypes = null,
        ?MedicalSharedSectionPersister $sharedSections = null,
        ?MedicalAttachmentManager $attachments = null,
        ?MedicalAnimalStatusSynchronizer $animalStatus = null
    )
    {
        $this->records = $records ?? new MedicalRecord();
        $this->animals = $animals ?? new Animal();
        $this->audit = $audit ?? new AuditService();
        $this->procedureConfig = $procedureConfig ?? new MedicalProcedureConfig();
        $this->attachments = $attachments ?? new MedicalAttachmentManager();

        $treatments = new TreatmentRecord();
        $this->payloadFactory = $payloadFactory ?? new MedicalPayloadFactory($treatments);
        $this->treatmentInventory = $treatmentInventory ?? new TreatmentInventorySynchronizer(new \App\Models\InventoryItem(), new \App\Models\StockTransaction());
        $this->subtypes = $subtypes ?? new MedicalSubtypePersister(
            new VaccinationRecord(),
            new SurgeryRecord(),
            new ExaminationRecord(),
            $treatments,
            new DewormingRecord(),
            new EuthanasiaRecord()
        );
        $this->sharedSections = $sharedSections ?? new MedicalSharedSectionPersister(
            new VitalSign(),
            new MedicalPrescription(),
            new MedicalLabResult(),
            $this->attachments
        );
        $this->animalStatus = $animalStatus ?? new MedicalAnimalStatusSynchronizer($this->animals);
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

        $record['details'] = $this->subtypes->record((int) $record['id'], (string) $record['procedure_type']);
        $record['vital_signs'] = $this->sharedSections->vitalSigns((int) $record['id']);
        $record['prescriptions'] = $this->sharedSections->prescriptions((int) $record['id']);
        $record['lab_results'] = $this->sharedSections->labResults((int) $record['id']);

        return $record;
    }

    public function byAnimal(int $animalId): array
    {
        if ($this->animals->find($animalId) === false) {
            throw new RuntimeException('Animal not found.');
        }

        $records = $this->records->listByAnimal($animalId);

        foreach ($records as &$record) {
            $record['details'] = $this->subtypes->record((int) $record['id'], (string) $record['procedure_type']);
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

            $this->subtypes->persist($type, $medicalRecordId, $detailPayload, true);
            $attachmentSync = $this->sharedSections->save($medicalRecordId, $data, $request->file('lab_attachments'));

            if ($type === 'treatment') {
                $this->treatmentInventory->sync(null, $detailPayload, $userId, $medicalRecordId);
            }

            $this->animalStatus->syncAfterWrite($type, (int) $data['animal_id'], $detailPayload, $userId);

            Database::commit();
        } catch (\Throwable $exception) {
            Database::rollBack();
            $this->attachments->deleteStoredFiles($attachmentSync['new_files']);
            throw $exception;
        }

        $this->attachments->deleteStoredFiles($attachmentSync['obsolete_files']);

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

            $this->subtypes->persist($type, $id, $detailPayload, false);
            $attachmentSync = $this->sharedSections->save($id, $data, $request->file('lab_attachments'), $existingLabResults);
            $this->animalStatus->syncAfterWrite($type, (int) $current['animal_id'], $detailPayload, $userId);

            Database::commit();
        } catch (\Throwable $exception) {
            Database::rollBack();
            $this->attachments->deleteStoredFiles($attachmentSync['new_files']);
            throw $exception;
        }

        $this->attachments->deleteStoredFiles($attachmentSync['obsolete_files']);

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
}

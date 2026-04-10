<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Request;
use App\Models\Animal;
use App\Models\MedicalRecord;
use App\Models\User as UserModel;
use App\Models\InventoryItem;
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
    public function __construct(
        private readonly MedicalRecord $records,
        private readonly Animal $animals,
        private readonly AuditService $audit,
        private readonly MedicalProcedureConfig $procedureConfig,
        private readonly MedicalPayloadFactory $payloadFactory,
        private readonly TreatmentInventorySynchronizer $treatmentInventory,
        private readonly MedicalSubtypePersister $subtypes,
        private readonly MedicalSharedSectionPersister $sharedSections,
        private readonly MedicalAttachmentManager $attachments,
        private readonly MedicalAnimalStatusSynchronizer $animalStatus,
        private readonly UserModel $users,
        private readonly InventoryItem $inventory
    ) {
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

        $this->records->db->beginTransaction();

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

            $this->records->db->commit();
        } catch (\Throwable $exception) {
            $this->records->db->rollBack();
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
        $existingLabResults = $this->sharedSections->labResults($id);
        $attachmentSync = [
            'new_files' => [],
            'obsolete_files' => [],
        ];

        $this->records->db->beginTransaction();

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

            $this->records->db->commit();
        } catch (\Throwable $exception) {
            $this->records->db->rollBack();
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

        $this->records->db->beginTransaction();

        try {
            if ((string) $current['procedure_type'] === 'treatment') {
                $this->treatmentInventory->restore($current['details'], $userId, $id);
            }

            $this->records->setDeleted($id, true);
            $this->records->db->commit();
        } catch (\Throwable $exception) {
            $this->records->db->rollBack();
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
        return $this->users->listPractitioners();
    }

    public function animalOptions(): array
    {
        return $this->animals->searchOptions();
    }

    public function treatmentInventoryOptions(): array
    {
        return $this->inventory->listForProcedures();
    }

    public function formConfig(string $type): array
    {
        return $this->procedureConfig->forType($type);
    }
}

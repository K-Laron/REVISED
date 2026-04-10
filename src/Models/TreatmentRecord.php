<?php

declare(strict_types=1);

namespace App\Models;

class TreatmentRecord extends BaseModel
{
    protected static string $table = 'treatment_records';
    protected static bool $useSoftDeletes = false; // Linked to medical_records soft delete

    public function findByMedicalRecord(int $medicalRecordId): array|false
    {
        return $this->db->fetch(
            'SELECT tr.*, ii.sku AS inventory_sku, ii.name AS inventory_item_name
             FROM treatment_records tr
             LEFT JOIN inventory_items ii ON ii.id = tr.inventory_item_id
             WHERE tr.medical_record_id = :medical_record_id
             LIMIT 1',
            ['medical_record_id' => $medicalRecordId]
        );
    }

    public function updateByMedicalRecord(int $medicalRecordId, array $data): void
    {
        $data['medical_record_id'] = $medicalRecordId;

        $this->db->execute(
            'UPDATE treatment_records
             SET diagnosis = :diagnosis,
                 medication_name = :medication_name,
                 dosage = :dosage,
                 route = :route,
                 frequency = :frequency,
                 duration_days = :duration_days,
                 start_date = :start_date,
                 end_date = :end_date,
                 quantity_dispensed = :quantity_dispensed,
                 inventory_item_id = :inventory_item_id,
                 special_instructions = :special_instructions
             WHERE medical_record_id = :medical_record_id',
            $data
        );
    }
}

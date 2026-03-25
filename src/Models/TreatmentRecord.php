<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class TreatmentRecord
{
    public function findByMedicalRecord(int $medicalRecordId): array|false
    {
        return Database::fetch(
            'SELECT tr.*, ii.sku AS inventory_sku, ii.name AS inventory_item_name
             FROM treatment_records tr
             LEFT JOIN inventory_items ii ON ii.id = tr.inventory_item_id
             WHERE tr.medical_record_id = :medical_record_id
             LIMIT 1',
            ['medical_record_id' => $medicalRecordId]
        );
    }

    public function create(array $data): void
    {
        Database::execute(
            'INSERT INTO treatment_records (
                medical_record_id, diagnosis, medication_name, dosage, route, frequency,
                duration_days, start_date, end_date, quantity_dispensed, inventory_item_id, special_instructions
             ) VALUES (
                :medical_record_id, :diagnosis, :medication_name, :dosage, :route, :frequency,
                :duration_days, :start_date, :end_date, :quantity_dispensed, :inventory_item_id, :special_instructions
             )',
            $data
        );
    }

    public function updateByMedicalRecord(int $medicalRecordId, array $data): void
    {
        $data['medical_record_id'] = $medicalRecordId;

        Database::execute(
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

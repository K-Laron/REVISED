<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class DewormingRecord
{
    public function findByMedicalRecord(int $medicalRecordId): array|false
    {
        return Database::fetch(
            'SELECT * FROM deworming_records WHERE medical_record_id = :medical_record_id LIMIT 1',
            ['medical_record_id' => $medicalRecordId]
        );
    }

    public function create(array $data): void
    {
        Database::execute(
            'INSERT INTO deworming_records (
                medical_record_id, dewormer_name, brand, dosage, weight_at_treatment_kg, next_due_date
             ) VALUES (
                :medical_record_id, :dewormer_name, :brand, :dosage, :weight_at_treatment_kg, :next_due_date
             )',
            $data
        );
    }

    public function updateByMedicalRecord(int $medicalRecordId, array $data): void
    {
        $data['medical_record_id'] = $medicalRecordId;

        Database::execute(
            'UPDATE deworming_records
             SET dewormer_name = :dewormer_name,
                 brand = :brand,
                 dosage = :dosage,
                 weight_at_treatment_kg = :weight_at_treatment_kg,
                 next_due_date = :next_due_date
             WHERE medical_record_id = :medical_record_id',
            $data
        );
    }
}

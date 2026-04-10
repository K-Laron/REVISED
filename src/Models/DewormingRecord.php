<?php

declare(strict_types=1);

namespace App\Models;

class DewormingRecord extends BaseModel
{
    protected static string $table = 'deworming_records';
    protected static bool $useSoftDeletes = false; // Linked to medical_records soft delete

    public function findByMedicalRecord(int $medicalRecordId): array|false
    {
        return $this->db->fetch(
            'SELECT * FROM deworming_records WHERE medical_record_id = :medical_record_id LIMIT 1',
            ['medical_record_id' => $medicalRecordId]
        );
    }

    public function updateByMedicalRecord(int $medicalRecordId, array $data): void
    {
        $data['medical_record_id'] = $medicalRecordId;

        $this->db->execute(
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

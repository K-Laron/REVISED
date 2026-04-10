<?php

declare(strict_types=1);

namespace App\Models;

class VaccinationRecord extends BaseModel
{
    protected static string $table = 'vaccination_records';
    protected static bool $useSoftDeletes = false; // Linked to medical_records soft delete

    public function findByMedicalRecord(int $medicalRecordId): array|false
    {
        return $this->db->fetch(
            'SELECT * FROM vaccination_records WHERE medical_record_id = :medical_record_id LIMIT 1',
            ['medical_record_id' => $medicalRecordId]
        );
    }

    public function updateByMedicalRecord(int $medicalRecordId, array $data): void
    {
        $data['medical_record_id'] = $medicalRecordId;

        $this->db->execute(
            'UPDATE vaccination_records
             SET vaccine_name = :vaccine_name,
                 vaccine_brand = :vaccine_brand,
                 batch_lot_number = :batch_lot_number,
                 dosage_ml = :dosage_ml,
                 route = :route,
                 injection_site = :injection_site,
                 dose_number = :dose_number,
                 next_due_date = :next_due_date,
                 adverse_reactions = :adverse_reactions
             WHERE medical_record_id = :medical_record_id',
            $data
        );
    }
}

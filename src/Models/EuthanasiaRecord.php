<?php

declare(strict_types=1);

namespace App\Models;

class EuthanasiaRecord extends BaseModel
{
    protected static string $table = 'euthanasia_records';
    protected static bool $useSoftDeletes = false; // Linked to medical_records soft delete

    public function findByMedicalRecord(int $medicalRecordId): array|false
    {
        return $this->db->fetch(
            'SELECT er.*, CONCAT_WS(" ", u.first_name, u.last_name) AS authorized_by_name
             FROM euthanasia_records er
             INNER JOIN users u ON u.id = er.authorized_by
             WHERE er.medical_record_id = :medical_record_id
             LIMIT 1',
            ['medical_record_id' => $medicalRecordId]
        );
    }

    public function updateByMedicalRecord(int $medicalRecordId, array $data): void
    {
        $data['medical_record_id'] = $medicalRecordId;

        $this->db->execute(
            'UPDATE euthanasia_records
             SET reason_category = :reason_category,
                 reason_details = :reason_details,
                 authorized_by = :authorized_by,
                 method = :method,
                 drug_used = :drug_used,
                 drug_dosage = :drug_dosage,
                 time_of_death = :time_of_death,
                 death_confirmed = :death_confirmed,
                 disposal_method = :disposal_method
             WHERE medical_record_id = :medical_record_id',
            $data
        );
    }
}

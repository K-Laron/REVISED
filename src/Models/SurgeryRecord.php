<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class SurgeryRecord
{
    public function findByMedicalRecord(int $medicalRecordId): array|false
    {
        return Database::fetch(
            'SELECT * FROM surgery_records WHERE medical_record_id = :medical_record_id LIMIT 1',
            ['medical_record_id' => $medicalRecordId]
        );
    }

    public function create(array $data): void
    {
        Database::execute(
            'INSERT INTO surgery_records (
                medical_record_id, surgery_type, pre_op_weight_kg, anesthesia_type, anesthesia_drug,
                anesthesia_dosage, duration_minutes, surgical_notes, complications, post_op_instructions, follow_up_date
             ) VALUES (
                :medical_record_id, :surgery_type, :pre_op_weight_kg, :anesthesia_type, :anesthesia_drug,
                :anesthesia_dosage, :duration_minutes, :surgical_notes, :complications, :post_op_instructions, :follow_up_date
             )',
            $data
        );
    }

    public function updateByMedicalRecord(int $medicalRecordId, array $data): void
    {
        $data['medical_record_id'] = $medicalRecordId;

        Database::execute(
            'UPDATE surgery_records
             SET surgery_type = :surgery_type,
                 pre_op_weight_kg = :pre_op_weight_kg,
                 anesthesia_type = :anesthesia_type,
                 anesthesia_drug = :anesthesia_drug,
                 anesthesia_dosage = :anesthesia_dosage,
                 duration_minutes = :duration_minutes,
                 surgical_notes = :surgical_notes,
                 complications = :complications,
                 post_op_instructions = :post_op_instructions,
                 follow_up_date = :follow_up_date
             WHERE medical_record_id = :medical_record_id',
            $data
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class ExaminationRecord
{
    public function findByMedicalRecord(int $medicalRecordId): array|false
    {
        return Database::fetch(
            'SELECT * FROM examination_records WHERE medical_record_id = :medical_record_id LIMIT 1',
            ['medical_record_id' => $medicalRecordId]
        );
    }

    public function create(array $data): void
    {
        Database::execute(
            'INSERT INTO examination_records (
                medical_record_id, weight_kg, temperature_celsius, heart_rate_bpm, respiratory_rate,
                body_condition_score, eyes_status, eyes_notes, ears_status, ears_notes, teeth_gums_status,
                teeth_gums_notes, skin_coat_status, skin_coat_notes, musculoskeletal_status,
                musculoskeletal_notes, overall_assessment, recommendations
             ) VALUES (
                :medical_record_id, :weight_kg, :temperature_celsius, :heart_rate_bpm, :respiratory_rate,
                :body_condition_score, :eyes_status, :eyes_notes, :ears_status, :ears_notes, :teeth_gums_status,
                :teeth_gums_notes, :skin_coat_status, :skin_coat_notes, :musculoskeletal_status,
                :musculoskeletal_notes, :overall_assessment, :recommendations
             )',
            $data
        );
    }

    public function updateByMedicalRecord(int $medicalRecordId, array $data): void
    {
        $data['medical_record_id'] = $medicalRecordId;

        Database::execute(
            'UPDATE examination_records
             SET weight_kg = :weight_kg,
                 temperature_celsius = :temperature_celsius,
                 heart_rate_bpm = :heart_rate_bpm,
                 respiratory_rate = :respiratory_rate,
                 body_condition_score = :body_condition_score,
                 eyes_status = :eyes_status,
                 eyes_notes = :eyes_notes,
                 ears_status = :ears_status,
                 ears_notes = :ears_notes,
                 teeth_gums_status = :teeth_gums_status,
                 teeth_gums_notes = :teeth_gums_notes,
                 skin_coat_status = :skin_coat_status,
                 skin_coat_notes = :skin_coat_notes,
                 musculoskeletal_status = :musculoskeletal_status,
                 musculoskeletal_notes = :musculoskeletal_notes,
                 overall_assessment = :overall_assessment,
                 recommendations = :recommendations
             WHERE medical_record_id = :medical_record_id',
            $data
        );
    }
}

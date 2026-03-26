<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class VitalSign
{
    public function findByMedicalRecordId(int $medicalRecordId): array|false
    {
        return Database::fetch(
            'SELECT * FROM medical_vital_signs WHERE medical_record_id = :medical_record_id LIMIT 1',
            ['medical_record_id' => $medicalRecordId]
        );
    }

    public function upsert(int $medicalRecordId, array $data): void
    {
        $existing = $this->findByMedicalRecordId($medicalRecordId);

        if ($existing !== false) {
            Database::execute(
                'UPDATE medical_vital_signs SET
                    weight_kg = :weight_kg,
                    temperature_celsius = :temperature_celsius,
                    heart_rate_bpm = :heart_rate_bpm,
                    respiratory_rate = :respiratory_rate,
                    body_condition_score = :body_condition_score
                 WHERE medical_record_id = :medical_record_id',
                [
                    'medical_record_id' => $medicalRecordId,
                    'weight_kg' => $data['weight_kg'] ?? null,
                    'temperature_celsius' => $data['temperature_celsius'] ?? null,
                    'heart_rate_bpm' => $data['heart_rate_bpm'] ?? null,
                    'respiratory_rate' => $data['respiratory_rate'] ?? null,
                    'body_condition_score' => $data['body_condition_score'] ?? null,
                ]
            );
        } else {
            Database::execute(
                'INSERT INTO medical_vital_signs (medical_record_id, weight_kg, temperature_celsius, heart_rate_bpm, respiratory_rate, body_condition_score)
                 VALUES (:medical_record_id, :weight_kg, :temperature_celsius, :heart_rate_bpm, :respiratory_rate, :body_condition_score)',
                [
                    'medical_record_id' => $medicalRecordId,
                    'weight_kg' => $data['weight_kg'] ?? null,
                    'temperature_celsius' => $data['temperature_celsius'] ?? null,
                    'heart_rate_bpm' => $data['heart_rate_bpm'] ?? null,
                    'respiratory_rate' => $data['respiratory_rate'] ?? null,
                    'body_condition_score' => $data['body_condition_score'] ?? null,
                ]
            );
        }
    }
}

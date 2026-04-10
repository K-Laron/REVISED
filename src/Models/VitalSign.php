<?php

declare(strict_types=1);

namespace App\Models;

class VitalSign extends BaseModel
{
    protected static string $table = 'medical_vital_signs';
    protected static bool $useSoftDeletes = false; // Linked to medical_records soft delete

    public function findByMedicalRecordId(int $medicalRecordId): array|false
    {
        return $this->db->fetch(
            'SELECT * FROM medical_vital_signs WHERE medical_record_id = :medical_record_id LIMIT 1',
            ['medical_record_id' => $medicalRecordId]
        );
    }

    public function upsert(int $medicalRecordId, array $data): void
    {
        $existing = $this->findByMedicalRecordId($medicalRecordId);

        $payload = [
            'weight_kg' => $data['weight_kg'] ?? null,
            'temperature_celsius' => $data['temperature_celsius'] ?? null,
            'heart_rate_bpm' => $data['heart_rate_bpm'] ?? null,
            'respiratory_rate' => $data['respiratory_rate'] ?? null,
            'body_condition_score' => $data['body_condition_score'] ?? null,
        ];

        if ($existing !== false) {
            $this->db->execute(
                'UPDATE medical_vital_signs SET
                    weight_kg = :weight_kg,
                    temperature_celsius = :temperature_celsius,
                    heart_rate_bpm = :heart_rate_bpm,
                    respiratory_rate = :respiratory_rate,
                    body_condition_score = :body_condition_score
                 WHERE medical_record_id = :medical_record_id',
                array_merge($payload, ['medical_record_id' => $medicalRecordId])
            );
        } else {
            $this->create(array_merge($payload, ['medical_record_id' => $medicalRecordId]));
        }
    }
}

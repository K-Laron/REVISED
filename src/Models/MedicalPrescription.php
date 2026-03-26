<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class MedicalPrescription
{
    public function findByMedicalRecordId(int $medicalRecordId): array
    {
        return Database::fetchAll(
            'SELECT * FROM medical_prescriptions WHERE medical_record_id = :medical_record_id ORDER BY sort_order',
            ['medical_record_id' => $medicalRecordId]
        );
    }

    public function bulkReplaceForRecord(int $medicalRecordId, array $items): void
    {
        Database::execute(
            'DELETE FROM medical_prescriptions WHERE medical_record_id = :medical_record_id',
            ['medical_record_id' => $medicalRecordId]
        );

        foreach ($items as $index => $item) {
            if (empty(trim((string) ($item['medicine_name'] ?? '')))) {
                continue;
            }

            Database::execute(
                'INSERT INTO medical_prescriptions (medical_record_id, medicine_name, dosage, frequency, duration, quantity, instructions, sort_order)
                 VALUES (:medical_record_id, :medicine_name, :dosage, :frequency, :duration, :quantity, :instructions, :sort_order)',
                [
                    'medical_record_id' => $medicalRecordId,
                    'medicine_name' => (string) ($item['medicine_name'] ?? ''),
                    'dosage' => (string) ($item['dosage'] ?? ''),
                    'frequency' => (string) ($item['frequency'] ?? ''),
                    'duration' => ($item['duration'] ?? '') !== '' ? (string) $item['duration'] : null,
                    'quantity' => ($item['quantity'] ?? '') !== '' ? (int) $item['quantity'] : null,
                    'instructions' => ($item['instructions'] ?? '') !== '' ? (string) $item['instructions'] : null,
                    'sort_order' => $index,
                ]
            );
        }
    }
}

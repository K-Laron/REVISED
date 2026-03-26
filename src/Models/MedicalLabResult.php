<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class MedicalLabResult
{
    public function findByMedicalRecordId(int $medicalRecordId): array
    {
        return Database::fetchAll(
            'SELECT * FROM medical_lab_results WHERE medical_record_id = :medical_record_id ORDER BY sort_order',
            ['medical_record_id' => $medicalRecordId]
        );
    }

    public function bulkReplaceForRecord(int $medicalRecordId, array $items): void
    {
        Database::execute(
            'DELETE FROM medical_lab_results WHERE medical_record_id = :medical_record_id',
            ['medical_record_id' => $medicalRecordId]
        );

        foreach ($items as $index => $item) {
            if (empty(trim((string) ($item['test_name'] ?? '')))) {
                continue;
            }

            Database::execute(
                'INSERT INTO medical_lab_results (medical_record_id, test_name, result_value, normal_range, status, date_conducted, remarks, attachment_path, sort_order)
                 VALUES (:medical_record_id, :test_name, :result_value, :normal_range, :status, :date_conducted, :remarks, :attachment_path, :sort_order)',
                [
                    'medical_record_id' => $medicalRecordId,
                    'test_name' => (string) ($item['test_name'] ?? ''),
                    'result_value' => ($item['result_value'] ?? '') !== '' ? (string) $item['result_value'] : null,
                    'normal_range' => ($item['normal_range'] ?? '') !== '' ? (string) $item['normal_range'] : null,
                    'status' => ($item['status'] ?? '') !== '' ? (string) $item['status'] : 'Pending',
                    'date_conducted' => ($item['date_conducted'] ?? '') !== '' ? (string) $item['date_conducted'] : null,
                    'remarks' => ($item['remarks'] ?? '') !== '' ? (string) $item['remarks'] : null,
                    'attachment_path' => ($item['attachment_path'] ?? '') !== '' ? (string) $item['attachment_path'] : null,
                    'sort_order' => $index,
                ]
            );
        }
    }
}

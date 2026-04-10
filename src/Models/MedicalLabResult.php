<?php

declare(strict_types=1);

namespace App\Models;

class MedicalLabResult extends BaseModel
{
    protected static string $table = 'medical_lab_results';
    protected static bool $useSoftDeletes = false; // Linked to medical_records soft delete

    public function findByMedicalRecordId(int $medicalRecordId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM medical_lab_results WHERE medical_record_id = :medical_record_id ORDER BY sort_order',
            ['medical_record_id' => $medicalRecordId]
        );
    }

    public function bulkReplaceForRecord(int $medicalRecordId, array $items): void
    {
        $this->db->execute(
            'DELETE FROM medical_lab_results WHERE medical_record_id = :medical_record_id',
            ['medical_record_id' => $medicalRecordId]
        );

        foreach ($items as $index => $item) {
            if (empty(trim((string) ($item['test_name'] ?? '')))) {
                continue;
            }

            $this->create([
                'medical_record_id' => $medicalRecordId,
                'test_name' => (string) ($item['test_name'] ?? ''),
                'result_value' => ($item['result_value'] ?? '') !== '' ? (string) $item['result_value'] : null,
                'normal_range' => ($item['normal_range'] ?? '') !== '' ? (string) $item['normal_range'] : null,
                'status' => ($item['status'] ?? '') !== '' ? (string) $item['status'] : 'Pending',
                'date_conducted' => ($item['date_conducted'] ?? '') !== '' ? (string) $item['date_conducted'] : null,
                'remarks' => ($item['remarks'] ?? '') !== '' ? (string) $item['remarks'] : null,
                'attachment_path' => ($item['attachment_path'] ?? '') !== '' ? (string) $item['attachment_path'] : null,
                'sort_order' => $index,
            ]);
        }
    }
}

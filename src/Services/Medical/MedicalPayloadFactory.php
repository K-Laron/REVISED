<?php

declare(strict_types=1);

namespace App\Services\Medical;

use App\Models\TreatmentRecord;
use App\Support\InputNormalizer;
use RuntimeException;

class MedicalPayloadFactory
{
    public function __construct(
        private readonly TreatmentRecord $treatments
    ) {
    }

    public function basePayload(string $type, array $data, int $userId, bool $creating): array
    {
        return [
            'animal_id' => (int) $data['animal_id'],
            'procedure_type' => $type,
            'record_date' => (string) InputNormalizer::dateTime($data['record_date']),
            'general_notes' => InputNormalizer::nullIfBlank($data['general_notes'] ?? null),
            'veterinarian_id' => (int) $data['veterinarian_id'],
            'created_by' => $creating ? $userId : null,
            'updated_by' => $userId,
        ];
    }

    public function subtypePayload(string $type, array $data, int $medicalRecordId, bool $creating): array
    {
        $payload = match ($type) {
            'vaccination' => [
                'medical_record_id' => $medicalRecordId,
                'vaccine_name' => trim((string) $data['vaccine_name']),
                'vaccine_brand' => InputNormalizer::nullIfBlank($data['vaccine_brand'] ?? null),
                'batch_lot_number' => InputNormalizer::nullIfBlank($data['batch_lot_number'] ?? null),
                'dosage_ml' => round((float) $data['dosage_ml'], 2),
                'route' => (string) $data['route'],
                'injection_site' => InputNormalizer::nullIfBlank($data['injection_site'] ?? null),
                'dose_number' => (int) $data['dose_number'],
                'next_due_date' => InputNormalizer::date($data['next_due_date'] ?? null) ?? $this->defaultDueDate($data['record_date'], 365),
                'adverse_reactions' => InputNormalizer::nullIfBlank($data['adverse_reactions'] ?? null),
            ],
            'surgery' => [
                'medical_record_id' => $medicalRecordId,
                'surgery_type' => (string) $data['surgery_type'],
                'pre_op_weight_kg' => InputNormalizer::decimalOrNull($data['pre_op_weight_kg'] ?? null),
                'anesthesia_type' => (string) $data['anesthesia_type'],
                'anesthesia_drug' => InputNormalizer::nullIfBlank($data['anesthesia_drug'] ?? null),
                'anesthesia_dosage' => InputNormalizer::nullIfBlank($data['anesthesia_dosage'] ?? null),
                'duration_minutes' => InputNormalizer::intOrNull($data['duration_minutes'] ?? null),
                'surgical_notes' => InputNormalizer::nullIfBlank($data['surgical_notes'] ?? null),
                'complications' => InputNormalizer::nullIfBlank($data['complications'] ?? null),
                'post_op_instructions' => InputNormalizer::nullIfBlank($data['post_op_instructions'] ?? null),
                'follow_up_date' => InputNormalizer::date($data['follow_up_date'] ?? null),
            ],
            'examination' => [
                'medical_record_id' => $medicalRecordId,
                'weight_kg' => InputNormalizer::decimalOrNull($data['weight_kg'] ?? null),
                'temperature_celsius' => InputNormalizer::decimalOrNull($data['temperature_celsius'] ?? null, 1),
                'heart_rate_bpm' => InputNormalizer::intOrNull($data['heart_rate_bpm'] ?? null),
                'respiratory_rate' => InputNormalizer::intOrNull($data['respiratory_rate'] ?? null),
                'body_condition_score' => InputNormalizer::intOrNull($data['body_condition_score'] ?? null),
                'eyes_status' => InputNormalizer::nullIfBlank($data['eyes_status'] ?? null),
                'eyes_notes' => InputNormalizer::nullIfBlank($data['eyes_notes'] ?? null),
                'ears_status' => InputNormalizer::nullIfBlank($data['ears_status'] ?? null),
                'ears_notes' => InputNormalizer::nullIfBlank($data['ears_notes'] ?? null),
                'teeth_gums_status' => InputNormalizer::nullIfBlank($data['teeth_gums_status'] ?? null),
                'teeth_gums_notes' => InputNormalizer::nullIfBlank($data['teeth_gums_notes'] ?? null),
                'skin_coat_status' => InputNormalizer::nullIfBlank($data['skin_coat_status'] ?? null),
                'skin_coat_notes' => InputNormalizer::nullIfBlank($data['skin_coat_notes'] ?? null),
                'musculoskeletal_status' => InputNormalizer::nullIfBlank($data['musculoskeletal_status'] ?? null),
                'musculoskeletal_notes' => InputNormalizer::nullIfBlank($data['musculoskeletal_notes'] ?? null),
                'overall_assessment' => InputNormalizer::nullIfBlank($data['overall_assessment'] ?? null),
                'recommendations' => InputNormalizer::nullIfBlank($data['recommendations'] ?? null),
            ],
            'treatment' => [
                'medical_record_id' => $medicalRecordId,
                'diagnosis' => trim((string) $data['diagnosis']),
                'medication_name' => trim((string) $data['medication_name']),
                'dosage' => trim((string) $data['dosage']),
                'route' => (string) $data['route'],
                'frequency' => trim((string) $data['frequency']),
                'duration_days' => InputNormalizer::intOrNull($data['duration_days'] ?? null),
                'start_date' => (string) InputNormalizer::date($data['start_date']),
                'end_date' => InputNormalizer::date($data['end_date'] ?? null),
                'quantity_dispensed' => InputNormalizer::intOrNull($data['quantity_dispensed'] ?? null),
                'inventory_item_id' => InputNormalizer::intOrNull($data['inventory_item_id'] ?? null),
                'special_instructions' => InputNormalizer::nullIfBlank($data['special_instructions'] ?? null),
            ],
            'deworming' => [
                'medical_record_id' => $medicalRecordId,
                'dewormer_name' => trim((string) $data['dewormer_name']),
                'brand' => InputNormalizer::nullIfBlank($data['brand'] ?? null),
                'dosage' => trim((string) $data['dosage']),
                'weight_at_treatment_kg' => InputNormalizer::decimalOrNull($data['weight_at_treatment_kg'] ?? null),
                'next_due_date' => InputNormalizer::date($data['next_due_date'] ?? null) ?? $this->defaultDueDate($data['record_date'], 90),
            ],
            'euthanasia' => [
                'medical_record_id' => $medicalRecordId,
                'reason_category' => (string) $data['reason_category'],
                'reason_details' => trim((string) $data['reason_details']),
                'authorized_by' => (int) $data['authorized_by'],
                'method' => trim((string) $data['method']),
                'drug_used' => InputNormalizer::nullIfBlank($data['drug_used'] ?? null),
                'drug_dosage' => InputNormalizer::nullIfBlank($data['drug_dosage'] ?? null),
                'time_of_death' => (string) InputNormalizer::dateTime($data['time_of_death']),
                'death_confirmed' => filter_var($data['death_confirmed'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
                'disposal_method' => (string) $data['disposal_method'],
            ],
            default => throw new RuntimeException('Unsupported medical procedure type.'),
        };

        if ($type === 'treatment' && ($payload['inventory_item_id'] === null || $payload['quantity_dispensed'] === null) && !$creating) {
            $current = $this->treatments->findByMedicalRecord($medicalRecordId);
            if ($current !== false) {
                $payload['inventory_item_id'] = $payload['inventory_item_id'] ?? (($current['inventory_item_id'] ?? null) !== null ? (int) $current['inventory_item_id'] : null);
                $payload['quantity_dispensed'] = $payload['quantity_dispensed'] ?? (($current['quantity_dispensed'] ?? null) !== null ? (int) $current['quantity_dispensed'] : null);
            }
        }

        return $payload;
    }

    private function defaultDueDate(mixed $recordDate, int $days): string
    {
        $recordAt = InputNormalizer::dateTime($recordDate) ?? date('Y-m-d H:i:s');

        return date('Y-m-d', strtotime($recordAt . ' +' . $days . ' days'));
    }
}

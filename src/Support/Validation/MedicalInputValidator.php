<?php

declare(strict_types=1);

namespace App\Support\Validation;

use App\Helpers\Validator;
use App\Support\InputNormalizer;

final class MedicalInputValidator
{
    public function normalizePayload(array $payload): array
    {
        $payload = InputNormalizer::normalizeDateTimeFields($payload, ['record_date', 'time_of_death']);

        return InputNormalizer::normalizeDateFields($payload, ['next_due_date', 'follow_up_date', 'start_date', 'end_date']);
    }

    public function validateForType(array $payload, string $type, mixed $labAttachments, bool $creating): Validator
    {
        $validator = (new Validator($payload))->rules($this->sharedRules($creating) + $this->typeRules($type));
        $this->validateLabAttachments($validator, $labAttachments);
        $this->validateLabResults($validator, $payload['lab_results'] ?? []);

        return $validator;
    }

    private function sharedRules(bool $creating): array
    {
        return [
            'animal_id' => $creating ? 'required|integer|exists:animals,id' : 'required|integer',
            'record_date' => 'required|date',
            'general_notes' => 'nullable|string|max:2000',
            'veterinarian_id' => 'required|integer|exists:users,id',
            'vs_weight_kg' => 'nullable|numeric|between:0.1,150',
            'vs_temperature_celsius' => 'nullable|numeric|between:35,43',
            'vs_heart_rate_bpm' => 'nullable|integer|between:30,300',
            'vs_respiratory_rate' => 'nullable|integer|between:5,100',
            'vs_body_condition_score' => 'nullable|integer|between:1,9',
        ];
    }

    private function typeRules(string $type): array
    {
        return match ($type) {
            'vaccination' => [
                'vaccine_name' => 'required|string|max:100',
                'vaccine_brand' => 'nullable|string|max:100',
                'batch_lot_number' => 'nullable|string|max:50',
                'dosage_ml' => 'required|numeric|between:0.01,100',
                'route' => 'required|in:Subcutaneous,Intramuscular,Oral',
                'injection_site' => 'nullable|string|max:50',
                'dose_number' => 'required|integer|between:1,10',
                'next_due_date' => 'nullable|date|after:record_date',
                'adverse_reactions' => 'nullable|string|max:1000',
            ],
            'surgery' => [
                'surgery_type' => 'required|in:Spay,Neuter,Tumor Removal,Amputation,Wound Repair,Other',
                'pre_op_weight_kg' => 'nullable|numeric|between:0.1,150',
                'anesthesia_type' => 'required|in:General,Local,Sedation',
                'anesthesia_drug' => 'nullable|string|max:100',
                'anesthesia_dosage' => 'nullable|string|max:50',
                'duration_minutes' => 'nullable|integer|between:1,1440',
                'surgical_notes' => 'nullable|string|max:2000',
                'complications' => 'nullable|string|max:1000',
                'post_op_instructions' => 'nullable|string|max:2000',
                'follow_up_date' => 'nullable|date|after:record_date',
            ],
            'examination' => [
                'weight_kg' => 'nullable|numeric|between:0.1,150',
                'temperature_celsius' => 'nullable|numeric|between:35,43',
                'heart_rate_bpm' => 'nullable|integer|between:30,300',
                'respiratory_rate' => 'nullable|integer|between:5,100',
                'body_condition_score' => 'nullable|integer|between:1,9',
                'eyes_status' => 'nullable|in:Normal,Abnormal',
                'eyes_notes' => 'nullable|string|max:1000',
                'ears_status' => 'nullable|in:Normal,Abnormal',
                'ears_notes' => 'nullable|string|max:1000',
                'teeth_gums_status' => 'nullable|in:Normal,Abnormal',
                'teeth_gums_notes' => 'nullable|string|max:1000',
                'skin_coat_status' => 'nullable|in:Normal,Abnormal',
                'skin_coat_notes' => 'nullable|string|max:1000',
                'musculoskeletal_status' => 'nullable|in:Normal,Abnormal',
                'musculoskeletal_notes' => 'nullable|string|max:1000',
                'overall_assessment' => 'nullable|string|max:2000',
                'recommendations' => 'nullable|string|max:2000',
            ],
            'treatment' => [
                'diagnosis' => 'required|string|max:255',
                'medication_name' => 'required|string|max:150',
                'dosage' => 'required|string|max:100',
                'route' => 'required|in:Oral,Injection,Topical,IV',
                'frequency' => 'required|string|max:50',
                'duration_days' => 'nullable|integer|between:1,365',
                'start_date' => 'required|date',
                'end_date' => 'nullable|date|after:start_date',
                'quantity_dispensed' => 'nullable|integer|between:1,1000',
                'inventory_item_id' => 'nullable|integer|exists:inventory_items,id',
                'special_instructions' => 'nullable|string|max:1000',
            ],
            'deworming' => [
                'dewormer_name' => 'required|string|max:100',
                'brand' => 'nullable|string|max:100',
                'dosage' => 'required|string|max:100',
                'weight_at_treatment_kg' => 'nullable|numeric|between:0.1,150',
                'next_due_date' => 'nullable|date|after:record_date',
            ],
            'euthanasia' => [
                'reason_category' => 'required|in:Medical,Behavioral,Legal/Court Order,Population Management',
                'reason_details' => 'required|string|min:10|max:2000',
                'authorized_by' => 'required|integer|exists:users,id',
                'method' => 'required|string|max:50',
                'drug_used' => 'nullable|string|max:100',
                'drug_dosage' => 'nullable|string|max:50',
                'time_of_death' => 'required|date',
                'disposal_method' => 'required|in:Cremation,Burial',
            ],
            default => [],
        };
    }

    private function validateLabAttachments(Validator $validator, mixed $files): void
    {
        if ($files === null || !is_array($files) || !isset($files['name'])) {
            return;
        }

        $names = is_array($files['name']) ? $files['name'] : [$files['name']];
        $sizes = is_array($files['size']) ? $files['size'] : [$files['size']];

        foreach ($names as $index => $name) {
            if (!$name) {
                continue;
            }

            $extension = strtolower(pathinfo((string) $name, PATHINFO_EXTENSION));
            if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
                $validator->addManualError('lab_attachments', 'Lab result attachments must be JPG, JPEG, PNG, WebP, or GIF images.');
                break;
            }

            if ((int) ($sizes[$index] ?? 0) > (10 * 1024 * 1024)) {
                $validator->addManualError('lab_attachments', 'Each lab result attachment must not exceed 10MB.');
                break;
            }
        }
    }

    private function validateLabResults(Validator $validator, mixed $labResults): void
    {
        if (is_string($labResults)) {
            $labResults = json_decode($labResults, true) ?: [];
        }

        if (!is_array($labResults)) {
            return;
        }

        foreach ($labResults as $row) {
            if (!is_array($row)) {
                continue;
            }

            $hasContent = false;
            foreach ($row as $key => $value) {
                if ($key === 'attachment_index') {
                    $hasContent = true;
                    break;
                }

                if (trim((string) $value) !== '') {
                    $hasContent = true;
                    break;
                }
            }

            if ($hasContent && trim((string) ($row['test_name'] ?? '')) === '') {
                $validator->addManualError('lab_results', 'Each lab or imaging entry requires a test name.');
                break;
            }
        }
    }
}

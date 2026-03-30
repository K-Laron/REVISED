<?php

declare(strict_types=1);

namespace App\Services\Medical;

use RuntimeException;

class MedicalProcedureConfig
{
    private const CONFIGS = [
        'vaccination' => [
            'label' => 'Vaccination',
            'endpoint' => '/api/medical/vaccination',
            'default_due_days' => 365,
            'fields' => ['vaccine_name', 'vaccine_brand', 'batch_lot_number', 'dosage_ml', 'route', 'injection_site', 'dose_number', 'next_due_date', 'adverse_reactions'],
        ],
        'surgery' => [
            'label' => 'Surgery',
            'endpoint' => '/api/medical/surgery',
            'fields' => ['surgery_type', 'pre_op_weight_kg', 'anesthesia_type', 'anesthesia_drug', 'anesthesia_dosage', 'duration_minutes', 'surgical_notes', 'complications', 'post_op_instructions', 'follow_up_date'],
        ],
        'examination' => [
            'label' => 'Examination',
            'endpoint' => '/api/medical/examination',
            'fields' => ['weight_kg', 'temperature_celsius', 'heart_rate_bpm', 'respiratory_rate', 'body_condition_score', 'overall_assessment', 'recommendations'],
        ],
        'treatment' => [
            'label' => 'Treatment',
            'endpoint' => '/api/medical/treatment',
            'fields' => ['diagnosis', 'medication_name', 'dosage', 'route', 'frequency', 'duration_days', 'start_date', 'end_date', 'quantity_dispensed', 'inventory_item_id', 'special_instructions'],
        ],
        'deworming' => [
            'label' => 'Deworming',
            'endpoint' => '/api/medical/deworming',
            'default_due_days' => 90,
            'fields' => ['dewormer_name', 'brand', 'dosage', 'weight_at_treatment_kg', 'next_due_date'],
        ],
        'euthanasia' => [
            'label' => 'Euthanasia',
            'endpoint' => '/api/medical/euthanasia',
            'fields' => ['reason_category', 'reason_details', 'authorized_by', 'method', 'drug_used', 'drug_dosage', 'time_of_death', 'disposal_method'],
        ],
    ];

    public function forType(string $type): array
    {
        if (!isset(self::CONFIGS[$type])) {
            throw new RuntimeException('Unsupported medical procedure type.');
        }

        return self::CONFIGS[$type];
    }
}

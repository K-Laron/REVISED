<?php

declare(strict_types=1);

namespace App\Services\Search;

use App\Support\InputNormalizer;

final class SearchFilterCatalog
{
    private const SECONDARY_FILTERS = [
        'animals_status' => [
            'module' => 'animals',
            'label' => 'Animal Status',
            'options' => [
                ['value' => 'Available', 'label' => 'Available'],
                ['value' => 'Adopted', 'label' => 'Adopted'],
                ['value' => 'Under Medical Care', 'label' => 'Under Medical Care'],
                ['value' => 'Quarantine', 'label' => 'Quarantine'],
            ],
        ],
        'medical_type' => [
            'module' => 'medical',
            'label' => 'Procedure Type',
            'options' => [
                ['value' => 'vaccination', 'label' => 'Vaccination'],
                ['value' => 'treatment', 'label' => 'Treatment'],
                ['value' => 'surgery', 'label' => 'Surgery'],
                ['value' => 'examination', 'label' => 'Examination'],
            ],
        ],
        'adoption_status' => [
            'module' => 'adoptions',
            'label' => 'Adoption Status',
            'options' => [
                ['value' => 'pending_review', 'label' => 'Pending Review'],
                ['value' => 'interview_scheduled', 'label' => 'Interview Scheduled'],
                ['value' => 'completed', 'label' => 'Completed'],
                ['value' => 'rejected', 'label' => 'Rejected'],
            ],
        ],
        'billing_status' => [
            'module' => 'billing',
            'label' => 'Payment Status',
            'options' => [
                ['value' => 'unpaid', 'label' => 'Unpaid'],
                ['value' => 'partial', 'label' => 'Partial'],
                ['value' => 'paid', 'label' => 'Paid'],
                ['value' => 'void', 'label' => 'Void'],
            ],
        ],
        'inventory_status' => [
            'module' => 'inventory',
            'label' => 'Inventory State',
            'options' => [
                ['value' => 'low_stock', 'label' => 'Low Stock'],
                ['value' => 'expiring', 'label' => 'Expiring Soon'],
                ['value' => 'active', 'label' => 'Active'],
                ['value' => 'inactive', 'label' => 'Inactive'],
            ],
        ],
        'users_status' => [
            'module' => 'users',
            'label' => 'User State',
            'options' => [
                ['value' => 'active', 'label' => 'Active'],
                ['value' => 'inactive', 'label' => 'Inactive'],
            ],
        ],
    ];

    private const STATUS_FILTERS = [
        'animal_available' => ['key' => 'animals_status', 'value' => 'Available'],
        'animal_adopted' => ['key' => 'animals_status', 'value' => 'Adopted'],
        'animal_medical' => ['key' => 'animals_status', 'value' => 'Under Medical Care'],
        'animal_quarantine' => ['key' => 'animals_status', 'value' => 'Quarantine'],
        'medical_vaccination' => ['key' => 'medical_type', 'value' => 'vaccination'],
        'medical_treatment' => ['key' => 'medical_type', 'value' => 'treatment'],
        'medical_surgery' => ['key' => 'medical_type', 'value' => 'surgery'],
        'medical_examination' => ['key' => 'medical_type', 'value' => 'examination'],
        'adoption_pending' => ['key' => 'adoption_status', 'value' => 'pending_review'],
        'adoption_completed' => ['key' => 'adoption_status', 'value' => 'completed'],
        'adoption_rejected' => ['key' => 'adoption_status', 'value' => 'rejected'],
        'billing_unpaid' => ['key' => 'billing_status', 'value' => 'unpaid'],
        'billing_partial' => ['key' => 'billing_status', 'value' => 'partial'],
        'billing_paid' => ['key' => 'billing_status', 'value' => 'paid'],
        'inventory_low_stock' => ['key' => 'inventory_status', 'value' => 'low_stock'],
        'inventory_expiring' => ['key' => 'inventory_status', 'value' => 'expiring'],
        'inventory_active' => ['key' => 'inventory_status', 'value' => 'active'],
        'inventory_inactive' => ['key' => 'inventory_status', 'value' => 'inactive'],
        'user_active' => ['key' => 'users_status', 'value' => 'active'],
        'user_inactive' => ['key' => 'users_status', 'value' => 'inactive'],
    ];

    public function normalize(array $filters): array
    {
        $normalized = [
            'modules' => $filters['modules'] ?? [],
            'per_section' => max(1, min(10, (int) ($filters['per_section'] ?? 5))),
            'date_from' => InputNormalizer::date($filters['date_from'] ?? null, true),
            'date_to' => InputNormalizer::date($filters['date_to'] ?? null, true),
        ];

        foreach (array_keys(self::SECONDARY_FILTERS) as $key) {
            $normalized[$key] = trim((string) ($filters[$key] ?? ''));
        }

        $legacyStatus = mb_strtolower(trim((string) ($filters['status'] ?? '')));
        if ($legacyStatus !== '' && isset(self::STATUS_FILTERS[$legacyStatus])) {
            $legacyFilter = self::STATUS_FILTERS[$legacyStatus];
            $targetKey = (string) ($legacyFilter['key'] ?? '');
            if ($targetKey !== '' && ($normalized[$targetKey] ?? '') === '') {
                $normalized[$targetKey] = (string) ($legacyFilter['value'] ?? '');
            }
        }

        return $normalized;
    }

    public function appliedFilters(array $selectedModules, array $filters): array
    {
        $applied = [
            'modules' => $selectedModules,
            'per_section' => $filters['per_section'],
            'date_from' => $filters['date_from'] ?? '',
            'date_to' => $filters['date_to'] ?? '',
        ];

        foreach (array_keys(self::SECONDARY_FILTERS) as $key) {
            $applied[$key] = $filters[$key] ?? '';
        }

        return $applied;
    }

    public function availableSecondaryFilters(array $moduleKeys): array
    {
        $availableModules = array_fill_keys($moduleKeys, true);
        $filters = [];

        foreach (self::SECONDARY_FILTERS as $key => $definition) {
            $module = (string) ($definition['module'] ?? '');
            if (!isset($availableModules[$module])) {
                continue;
            }

            $filters[$key] = [
                'key' => $key,
                'module' => $module,
                'label' => (string) ($definition['label'] ?? $key),
                'options' => $definition['options'] ?? [],
            ];
        }

        return $filters;
    }
}

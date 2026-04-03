<?php

declare(strict_types=1);

namespace App\Services\Search;

use App\Support\InputNormalizer;

final class SearchFilterCatalog
{
    private SearchModuleCatalog $moduleCatalog;

    public function __construct(?SearchModuleCatalog $moduleCatalog = null)
    {
        $this->moduleCatalog = $moduleCatalog ?? new SearchModuleCatalog([]);
    }

    public function normalize(array $filters): array
    {
        $normalized = [
            'modules' => $filters['modules'] ?? [],
            'per_section' => max(1, min(10, (int) ($filters['per_section'] ?? 5))),
            'date_from' => InputNormalizer::date($filters['date_from'] ?? null, true),
            'date_to' => InputNormalizer::date($filters['date_to'] ?? null, true),
        ];

        foreach (array_keys($this->moduleCatalog->allSecondaryFilters()) as $key) {
            $normalized[$key] = trim((string) ($filters[$key] ?? ''));
        }

        $legacyStatus = mb_strtolower(trim((string) ($filters['status'] ?? '')));
        $legacyStatusFilters = $this->moduleCatalog->legacyStatusFilters();
        if ($legacyStatus !== '' && isset($legacyStatusFilters[$legacyStatus])) {
            $legacyFilter = $legacyStatusFilters[$legacyStatus];
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

        foreach (array_keys($this->moduleCatalog->allSecondaryFilters()) as $key) {
            $applied[$key] = $filters[$key] ?? '';
        }

        return $applied;
    }

    public function availableSecondaryFilters(array $moduleKeys): array
    {
        return $this->moduleCatalog->secondaryFilters($moduleKeys);
    }
}

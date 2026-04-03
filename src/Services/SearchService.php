<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Search\Providers\AdoptionsSearchProvider;
use App\Services\Search\Providers\AnimalsSearchProvider;
use App\Services\Search\Providers\BillingSearchProvider;
use App\Services\Search\Providers\InventorySearchProvider;
use App\Services\Search\Providers\MedicalSearchProvider;
use App\Services\Search\Providers\UsersSearchProvider;
use App\Services\Search\SearchFilterCatalog;
use App\Services\Search\SearchModuleCatalog;
use App\Services\Search\SearchProviderInterface;

class SearchService
{
    /** @var array<string, SearchProviderInterface> */
    private array $providers = [];

    private SearchFilterCatalog $filterCatalog;

    public function __construct(array $providers = [], ?SearchFilterCatalog $filterCatalog = null)
    {
        foreach ($providers !== [] ? $providers : $this->defaultProviders() as $provider) {
            $this->providers[$provider->key()] = $provider;
        }

        $this->filterCatalog = $filterCatalog ?? new SearchFilterCatalog(
            new SearchModuleCatalog(array_values($this->providers))
        );
    }

    public function search(string $query, array $user, array $filters = []): array
    {
        $term = trim($query);
        $normalizedFilters = $this->filterCatalog->normalize($filters);
        $perSection = $normalizedFilters['per_section'];
        $availableModules = $this->availableModules($user);
        $selectedModules = $this->selectedModules($normalizedFilters['modules'], $availableModules);

        if (mb_strlen($term) < 2) {
            return [
                'query' => $term,
                'total_results' => 0,
                'sections' => [],
                'applied_filters' => $this->filterCatalog->appliedFilters($selectedModules, $normalizedFilters),
            ];
        }

        $sections = [];
        foreach ($selectedModules as $moduleKey) {
            $provider = $this->providers[$moduleKey] ?? null;
            if ($provider === null) {
                continue;
            }

            $sections[] = $provider->search($term, $perSection, $normalizedFilters);
        }

        $sections = array_values(array_filter($sections, static fn (array $section): bool => $section['count'] > 0));
        $totalResults = array_sum(array_column($sections, 'count'));

        return [
            'query' => $term,
            'total_results' => $totalResults,
            'sections' => $sections,
            'applied_filters' => $this->filterCatalog->appliedFilters($selectedModules, $normalizedFilters),
        ];
    }

    public function availableModules(array $user): array
    {
        $modules = [];

        foreach ($this->providers as $provider) {
            if (!$this->canAccess($user, $provider->permission())) {
                continue;
            }

            $modules[] = [
                'key' => $provider->key(),
                'label' => $provider->label(),
            ];
        }

        return $modules;
    }

    public function availableSecondaryFilters(array $user): array
    {
        return $this->filterCatalog->availableSecondaryFilters(array_column($this->availableModules($user), 'key'));
    }

    private function canAccess(array $user, string $permission): bool
    {
        if (($user['role_name'] ?? '') === 'super_admin') {
            return true;
        }

        return in_array($permission, $user['permissions'] ?? [], true);
    }

    private function selectedModules(array|string $modules, array $availableModules): array
    {
        $availableKeys = array_column($availableModules, 'key');
        $requested = is_array($modules) ? $modules : [$modules];
        $requested = array_values(array_filter(array_map(static fn (mixed $value): string => trim((string) $value), $requested)));
        $selected = array_values(array_intersect($availableKeys, $requested));

        return $selected !== [] ? $selected : $availableKeys;
    }

    /**
     * @return list<SearchProviderInterface>
     */
    private function defaultProviders(): array
    {
        return [
            new AnimalsSearchProvider(),
            new MedicalSearchProvider(),
            new AdoptionsSearchProvider(),
            new BillingSearchProvider(),
            new InventorySearchProvider(),
            new UsersSearchProvider(),
        ];
    }
}

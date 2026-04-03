<?php

declare(strict_types=1);

namespace App\Services\Search;

final class SearchModuleCatalog
{
    /** @var array<string, SearchProviderInterface> */
    private array $providers = [];

    /**
     * @param list<SearchProviderInterface> $providers
     */
    public function __construct(array $providers)
    {
        foreach ($providers as $provider) {
            $this->providers[$provider->key()] = $provider;
        }
    }

    public function allSecondaryFilters(): array
    {
        return $this->secondaryFilters(array_keys($this->providers));
    }

    public function secondaryFilters(array $moduleKeys): array
    {
        $available = array_fill_keys($moduleKeys, true);
        $filters = [];

        foreach ($this->providers as $provider) {
            if (!isset($available[$provider->key()])) {
                continue;
            }

            foreach ($this->providerSecondaryFilters($provider) as $key => $definition) {
                $filters[$key] = $definition;
            }
        }

        return $filters;
    }

    public function legacyStatusFilters(): array
    {
        $aliases = [];

        foreach ($this->providers as $provider) {
            if (!method_exists($provider, 'legacyStatusAliases')) {
                continue;
            }

            foreach ((array) $provider->legacyStatusAliases() as $alias => $definition) {
                $key = trim((string) ($definition['key'] ?? ''));
                $value = (string) ($definition['value'] ?? '');

                if ($alias === '' || $key === '' || $value === '') {
                    continue;
                }

                $aliases[(string) $alias] = [
                    'key' => $key,
                    'value' => $value,
                ];
            }
        }

        return $aliases;
    }

    private function providerSecondaryFilters(SearchProviderInterface $provider): array
    {
        if (!method_exists($provider, 'secondaryFilters')) {
            return [];
        }

        $filters = [];

        foreach ((array) $provider->secondaryFilters() as $key => $definition) {
            $filterKey = trim((string) $key);
            if ($filterKey === '') {
                continue;
            }

            $filters[$filterKey] = [
                'key' => $filterKey,
                'module' => $provider->key(),
                'label' => (string) ($definition['label'] ?? $filterKey),
                'options' => is_array($definition['options'] ?? null) ? $definition['options'] : [],
            ];
        }

        return $filters;
    }
}

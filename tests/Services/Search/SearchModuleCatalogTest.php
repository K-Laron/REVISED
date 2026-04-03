<?php

declare(strict_types=1);

namespace Tests\Services\Search;

use App\Services\Search\AbstractSearchProvider;
use App\Services\Search\SearchModuleCatalog;
use PHPUnit\Framework\TestCase;

final class SearchModuleCatalogTest extends TestCase
{
    public function testSecondaryFiltersAreBuiltFromProviderMetadata(): void
    {
        $catalog = new SearchModuleCatalog([
            $this->provider(
                'animals',
                'Animals',
                'animals.read',
                [
                    'animal_lifecycle' => [
                        'label' => 'Lifecycle',
                        'options' => [
                            ['value' => 'Archived', 'label' => 'Archived'],
                        ],
                    ],
                ]
            ),
            $this->provider(
                'users',
                'Users',
                'users.read',
                [
                    'account_state' => [
                        'label' => 'Account State',
                        'options' => [
                            ['value' => 'active', 'label' => 'Active'],
                        ],
                    ],
                ]
            ),
        ]);

        self::assertSame(
            [
                'animal_lifecycle' => [
                    'key' => 'animal_lifecycle',
                    'module' => 'animals',
                    'label' => 'Lifecycle',
                    'options' => [
                        ['value' => 'Archived', 'label' => 'Archived'],
                    ],
                ],
            ],
            $catalog->secondaryFilters(['animals'])
        );
    }

    public function testLegacyStatusAliasesAreCollectedFromProviders(): void
    {
        $catalog = new SearchModuleCatalog([
            $this->provider(
                'animals',
                'Animals',
                'animals.read',
                [
                    'animal_lifecycle' => [
                        'label' => 'Lifecycle',
                        'options' => [],
                    ],
                ],
                [
                    'animal_archived' => [
                        'key' => 'animal_lifecycle',
                        'value' => 'Archived',
                    ],
                ]
            ),
        ]);

        self::assertSame(
            [
                'animal_archived' => [
                    'key' => 'animal_lifecycle',
                    'value' => 'Archived',
                ],
            ],
            $catalog->legacyStatusFilters()
        );
    }

    private function provider(
        string $key,
        string $label,
        string $permission,
        array $secondaryFilters,
        array $legacyAliases = []
    ): AbstractSearchProvider {
        return new class ($key, $label, $permission, $secondaryFilters, $legacyAliases) extends AbstractSearchProvider {
            public function __construct(
                private readonly string $keyName,
                private readonly string $labelName,
                private readonly string $permissionName,
                private readonly array $secondaryFilterDefinitions,
                private readonly array $legacyStatusDefinitions
            ) {
            }

            public function key(): string
            {
                return $this->keyName;
            }

            public function label(): string
            {
                return $this->labelName;
            }

            public function permission(): string
            {
                return $this->permissionName;
            }

            public function search(string $term, int $limit, array $filters): array
            {
                return $this->section($this->keyName, $this->labelName, '/' . $this->keyName, 0, []);
            }

            public function secondaryFilters(): array
            {
                return $this->secondaryFilterDefinitions;
            }

            public function legacyStatusAliases(): array
            {
                return $this->legacyStatusDefinitions;
            }
        };
    }
}

<?php

declare(strict_types=1);

namespace Tests\Services\Search;

use App\Services\Search\AbstractSearchProvider;
use App\Services\Search\SearchProviderInterface;
use App\Services\SearchService;
use PHPUnit\Framework\TestCase;

final class SearchServiceTest extends TestCase
{
    public function testSearchDelegatesOnlyToAccessibleSelectedProvidersAndAggregatesResults(): void
    {
        if (!interface_exists(SearchProviderInterface::class)) {
            self::fail('Expected SearchProviderInterface to exist.');
        }

        $animals = $this->provider(
            'animals',
            'Animals',
            'animals.read',
            [
                'key' => 'animals',
                'label' => 'Animals',
                'href' => '/animals',
                'count' => 2,
                'items' => [
                    ['title' => 'Buddy', 'href' => '/animals/1'],
                    ['title' => 'Bantay', 'href' => '/animals/2'],
                ],
            ]
        );
        $billing = $this->provider(
            'billing',
            'Invoices',
            'billing.read',
            [
                'key' => 'billing',
                'label' => 'Invoices',
                'href' => '/billing',
                'count' => 1,
                'items' => [
                    ['title' => 'INV-001', 'href' => '/billing/invoices/1'],
                ],
            ]
        );

        $service = new SearchService([$animals, $billing]);

        $result = $service->search('Buddy', [
            'role_name' => 'shelter_staff',
            'permissions' => ['animals.read'],
        ], [
            'modules' => ['animals', 'billing'],
            'per_section' => 7,
        ]);

        self::assertSame(2, $result['total_results']);
        self::assertSame(['animals'], array_column($result['sections'], 'key'));
        self::assertCount(1, $animals->calls);
        self::assertSame('Buddy', $animals->calls[0]['term']);
        self::assertSame(7, $animals->calls[0]['limit']);
        self::assertSame(['animals', 'billing'], $animals->calls[0]['filters']['modules']);
        self::assertSame([], $billing->calls);
    }

    public function testSearchNormalizesLegacyStatusFiltersBeforeCallingProviders(): void
    {
        if (!interface_exists(SearchProviderInterface::class)) {
            self::fail('Expected SearchProviderInterface to exist.');
        }

        $animals = $this->provider(
            'animals',
            'Animals',
            'animals.read',
            [
                'key' => 'animals',
                'label' => 'Animals',
                'href' => '/animals',
                'count' => 0,
                'items' => [],
            ],
            [
                'animal_lifecycle' => [
                    'label' => 'Lifecycle',
                    'options' => [
                        ['value' => 'Adopted', 'label' => 'Adopted'],
                    ],
                ],
            ],
            [
                'animal_adopted' => [
                    'key' => 'animal_lifecycle',
                    'value' => 'Adopted',
                ],
            ]
        );

        $service = new SearchService([$animals]);

        $result = $service->search('Buddy', [
            'role_name' => 'super_admin',
            'permissions' => [],
        ], [
            'modules' => ['animals'],
            'status' => 'animal_adopted',
            'date_from' => '2026-03-01',
            'date_to' => 'invalid-date',
        ]);

        self::assertSame('Adopted', $animals->calls[0]['filters']['animal_lifecycle']);
        self::assertSame('2026-03-01', $animals->calls[0]['filters']['date_from']);
        self::assertNull($animals->calls[0]['filters']['date_to']);
        self::assertSame('Adopted', $result['applied_filters']['animal_lifecycle']);
        self::assertSame('2026-03-01', $result['applied_filters']['date_from']);
        self::assertSame('', $result['applied_filters']['date_to']);
    }

    public function testAvailableModulesAndSecondaryFiltersFollowAccessibleProviders(): void
    {
        if (!interface_exists(SearchProviderInterface::class)) {
            self::fail('Expected SearchProviderInterface to exist.');
        }

        $service = new SearchService([
            $this->provider('animals', 'Animals', 'animals.read', [
                'key' => 'animals',
                'label' => 'Animals',
                'href' => '/animals',
                'count' => 0,
                'items' => [],
            ], [
                'animal_lifecycle' => [
                    'label' => 'Lifecycle',
                    'options' => [
                        ['value' => 'Available', 'label' => 'Available'],
                    ],
                ],
            ]),
            $this->provider('users', 'Users', 'users.read', [
                'key' => 'users',
                'label' => 'Users',
                'href' => '/users',
                'count' => 0,
                'items' => [],
            ], [
                'account_state' => [
                    'label' => 'Account State',
                    'options' => [
                        ['value' => 'active', 'label' => 'Active'],
                    ],
                ],
            ]),
        ]);

        $user = [
            'role_name' => 'staff',
            'permissions' => ['animals.read'],
        ];

        self::assertSame([
            ['key' => 'animals', 'label' => 'Animals'],
        ], $service->availableModules($user));

        self::assertSame([
            'animal_lifecycle' => [
                'key' => 'animal_lifecycle',
                'module' => 'animals',
                'label' => 'Lifecycle',
                'options' => [
                    ['value' => 'Available', 'label' => 'Available'],
                ],
            ],
        ], $service->availableSecondaryFilters($user));
    }

    public function testAvailableSecondaryFiltersReuseAccessibleModuleMetadataWithinTheSameService(): void
    {
        if (!interface_exists(SearchProviderInterface::class)) {
            self::fail('Expected SearchProviderInterface to exist.');
        }

        $animals = $this->provider('animals', 'Animals', 'animals.read', [
            'key' => 'animals',
            'label' => 'Animals',
            'href' => '/animals',
            'count' => 0,
            'items' => [],
        ], [
            'animal_lifecycle' => [
                'label' => 'Lifecycle',
                'options' => [
                    ['value' => 'Available', 'label' => 'Available'],
                ],
            ],
        ]);
        $users = $this->provider('users', 'Users', 'users.read', [
            'key' => 'users',
            'label' => 'Users',
            'href' => '/users',
            'count' => 0,
            'items' => [],
        ]);

        $service = new SearchService([$animals, $users]);
        $user = [
            'role_name' => 'staff',
            'permissions' => ['animals.read'],
        ];

        $service->availableModules($user);
        $service->availableSecondaryFilters($user);
        $service->availableSecondaryFilters($user);

        self::assertSame(1, $animals->permissionCalls);
        self::assertSame(1, $users->permissionCalls);
        self::assertSame(1, $animals->labelCalls);
        self::assertSame(1, $animals->secondaryFilterCalls);
    }

    private function provider(
        string $key,
        string $label,
        string $permission,
        array $section,
        array $secondaryFilters = [],
        array $legacyStatusAliases = []
    ): object
    {
        return new class ($key, $label, $permission, $section, $secondaryFilters, $legacyStatusAliases) extends AbstractSearchProvider {
            /** @var array<int, array{term: string, limit: int, filters: array}> */
            public array $calls = [];
            public int $labelCalls = 0;
            public int $permissionCalls = 0;
            public int $secondaryFilterCalls = 0;

            public function __construct(
                private readonly string $key,
                private readonly string $label,
                private readonly string $permission,
                private readonly array $section,
                private readonly array $secondaryFilters,
                private readonly array $legacyStatusAliases
            ) {
            }

            public function key(): string
            {
                return $this->key;
            }

            public function label(): string
            {
                $this->labelCalls++;
                return $this->label;
            }

            public function permission(): string
            {
                $this->permissionCalls++;
                return $this->permission;
            }

            public function search(string $term, int $limit, array $filters): array
            {
                $this->calls[] = [
                    'term' => $term,
                    'limit' => $limit,
                    'filters' => $filters,
                ];

                return $this->section;
            }

            public function secondaryFilters(): array
            {
                $this->secondaryFilterCalls++;
                return $this->secondaryFilters;
            }

            public function legacyStatusAliases(): array
            {
                return $this->legacyStatusAliases;
            }
        };
    }
}

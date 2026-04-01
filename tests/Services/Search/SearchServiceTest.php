<?php

declare(strict_types=1);

namespace Tests\Services\Search;

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

        self::assertSame('Adopted', $animals->calls[0]['filters']['animals_status']);
        self::assertSame('2026-03-01', $animals->calls[0]['filters']['date_from']);
        self::assertNull($animals->calls[0]['filters']['date_to']);
        self::assertSame('Adopted', $result['applied_filters']['animals_status']);
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
            ]),
            $this->provider('users', 'Users', 'users.read', [
                'key' => 'users',
                'label' => 'Users',
                'href' => '/users',
                'count' => 0,
                'items' => [],
            ]),
        ]);

        $user = [
            'role_name' => 'staff',
            'permissions' => ['animals.read'],
        ];

        self::assertSame([
            ['key' => 'animals', 'label' => 'Animals'],
        ], $service->availableModules($user));

        self::assertArrayHasKey('animals_status', $service->availableSecondaryFilters($user));
        self::assertArrayNotHasKey('users_status', $service->availableSecondaryFilters($user));
    }

    private function provider(string $key, string $label, string $permission, array $section): object
    {
        return new class ($key, $label, $permission, $section) implements SearchProviderInterface {
            /** @var array<int, array{term: string, limit: int, filters: array}> */
            public array $calls = [];

            public function __construct(
                private readonly string $key,
                private readonly string $label,
                private readonly string $permission,
                private readonly array $section
            ) {
            }

            public function key(): string
            {
                return $this->key;
            }

            public function label(): string
            {
                return $this->label;
            }

            public function permission(): string
            {
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
        };
    }
}

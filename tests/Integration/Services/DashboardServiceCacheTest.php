<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Services\DashboardService;
use App\Support\Cache\FileCacheStore;
use App\Support\Performance\PerformanceProbe;
use Tests\Integration\DatabaseIntegrationTestCase;

require_once __DIR__ . '/../DatabaseIntegrationTestCase.php';

final class DashboardServiceCacheTest extends DatabaseIntegrationTestCase
{
    private string $cachePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cachePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'dashboard-service-cache-' . bin2hex(random_bytes(5)) . '.json';
    }

    protected function tearDown(): void
    {
        PerformanceProbe::reset();

        if (is_file($this->cachePath)) {
            @unlink($this->cachePath);
        }

        parent::tearDown();
    }

    public function testBootstrapCacheDropsTheSecondQueryCount(): void
    {
        $store = new FileCacheStore($this->cachePath);
        $this->container->singleton(FileCacheStore::class, $store);
        $service = $this->container->get(DashboardService::class);

        PerformanceProbe::forceEnabled(true);
        PerformanceProbe::startRequest('CLI', 'dashboard-bootstrap-first');
        $service->bootstrap();
        $first = PerformanceProbe::finishRequest();

        PerformanceProbe::startRequest('CLI', 'dashboard-bootstrap-second');
        $service->bootstrap();
        $second = PerformanceProbe::finishRequest();

        self::assertGreaterThan(0, $first['query_count']);
        self::assertLessThan($first['query_count'], $second['query_count']);
    }

    public function testStatsColdPathDoesNotNeedTheFullBootstrapQueryBundle(): void
    {
        $store = new FileCacheStore($this->cachePath);
        $this->container->singleton(FileCacheStore::class, $store);
        $service = $this->container->get(DashboardService::class);

        PerformanceProbe::forceEnabled(true);
        PerformanceProbe::startRequest('CLI', 'dashboard-bootstrap-cold');
        $service->bootstrap();
        $bootstrap = PerformanceProbe::finishRequest();

        if (is_file($this->cachePath)) {
            @unlink($this->cachePath);
        }

        $store = new FileCacheStore($this->cachePath);
        $this->container->singleton(FileCacheStore::class, $store);
        $service = $this->container->get(DashboardService::class);

        PerformanceProbe::startRequest('CLI', 'dashboard-stats-cold');
        $service->stats();
        $stats = PerformanceProbe::finishRequest();

        self::assertLessThan($bootstrap['query_count'], $stats['query_count']);
    }

    public function testBootstrapColdPathUsesThreeQueriesOrLess(): void
    {
        $store = new FileCacheStore($this->cachePath);
        $this->container->singleton(FileCacheStore::class, $store);
        $service = $this->container->get(DashboardService::class);

        PerformanceProbe::forceEnabled(true);
        PerformanceProbe::startRequest('CLI', 'dashboard-bootstrap-cold-optimized');
        $service->bootstrap();
        $bootstrap = PerformanceProbe::finishRequest();

        self::assertLessThanOrEqual(3, $bootstrap['query_count']);
    }

    public function testBootstrapPayloadPreservesChartAndActivityOrdering(): void
    {
        $store = new FileCacheStore($this->cachePath);
        $this->container->singleton(FileCacheStore::class, $store);
        $service = $this->container->get(DashboardService::class);

        $bootstrap = $service->bootstrap();
        $occupancyLabels = $bootstrap['charts']['occupancy']['labels'] ?? [];
        $medicalLabels = $bootstrap['charts']['medical']['labels'] ?? [];
        $activity = $bootstrap['activity'] ?? [];

        $sortedOccupancyLabels = $occupancyLabels;
        sort($sortedOccupancyLabels, SORT_STRING);

        self::assertSame($sortedOccupancyLabels, $occupancyLabels);

        $sortedMedicalLabels = $medicalLabels;
        sort($sortedMedicalLabels, SORT_STRING);

        self::assertSame($sortedMedicalLabels, $medicalLabels);

        $activityTimestamps = array_map(
            static fn (array $row): string => (string) ($row['created_at'] ?? ''),
            $activity
        );
        $sortedActivityTimestamps = $activityTimestamps;
        rsort($sortedActivityTimestamps, SORT_STRING);

        self::assertSame($sortedActivityTimestamps, $activityTimestamps);
    }

    public function testActionQueueUsesSummaryQueriesAcrossModules(): void
    {
        $service = $this->container->get(DashboardService::class);

        PerformanceProbe::forceEnabled(true);
        PerformanceProbe::startRequest('CLI', 'dashboard-action-queue-optimized');
        $service->actionQueue([
            'role_name' => 'super_admin',
            'permissions' => [],
        ]);
        $summary = PerformanceProbe::finishRequest();

        self::assertLessThanOrEqual(4, $summary['query_count']);
    }

    public function testActionQueueCacheDropsTheSecondQueryCount(): void
    {
        $store = new FileCacheStore($this->cachePath);
        $this->container->singleton(FileCacheStore::class, $store);
        $service = $this->container->get(DashboardService::class);
        $user = [
            'role_name' => 'super_admin',
            'permissions' => [],
        ];

        PerformanceProbe::forceEnabled(true);
        PerformanceProbe::startRequest('CLI', 'dashboard-action-queue-first');
        $service->actionQueue($user);
        $first = PerformanceProbe::finishRequest();

        PerformanceProbe::startRequest('CLI', 'dashboard-action-queue-second');
        $service->actionQueue($user);
        $second = PerformanceProbe::finishRequest();

        self::assertGreaterThan(0, $first['query_count']);
        self::assertLessThan($first['query_count'], $second['query_count']);
    }

    public function testActionQueueCacheIsScopedToTheUserAccessProfile(): void
    {
        $store = new FileCacheStore($this->cachePath);
        $this->container->singleton(FileCacheStore::class, $store);
        $service = $this->container->get(DashboardService::class);

        $adminItems = $service->actionQueue([
            'role_name' => 'super_admin',
            'permissions' => [],
        ]);

        $limitedItems = $service->actionQueue([
            'role_name' => 'shelter_staff',
            'permissions' => [],
        ]);

        self::assertNotSame([], $adminItems);
        self::assertSame([], $limitedItems);
    }
}

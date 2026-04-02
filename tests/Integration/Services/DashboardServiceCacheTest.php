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
        $service = new DashboardService($store);

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
}

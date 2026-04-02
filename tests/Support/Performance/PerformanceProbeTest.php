<?php

declare(strict_types=1);

namespace Tests\Support\Performance;

use App\Support\Performance\PerformanceProbe;
use PHPUnit\Framework\TestCase;

final class PerformanceProbeTest extends TestCase
{
    protected function tearDown(): void
    {
        PerformanceProbe::reset();
    }

    public function testFinishRequestBuildsSummaryAndHeaders(): void
    {
        PerformanceProbe::forceEnabled(true);
        PerformanceProbe::startRequest('GET', '/api/dashboard/stats');
        PerformanceProbe::recordQuery('SELECT 1', 4.25);
        PerformanceProbe::recordQuery('SELECT 2', 6.75);

        $summary = PerformanceProbe::finishRequest();
        $headers = PerformanceProbe::headersFromSummary($summary);

        self::assertSame('GET', $summary['method']);
        self::assertSame('/api/dashboard/stats', $summary['path']);
        self::assertSame(2, $summary['query_count']);
        self::assertSame(11.0, $summary['database_time_ms']);
        self::assertArrayHasKey('X-App-Query-Count', $headers);
        self::assertSame('2', $headers['X-App-Query-Count']);
    }
}

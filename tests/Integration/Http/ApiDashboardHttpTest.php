<?php

declare(strict_types=1);

namespace Tests\Integration\Http;

require_once __DIR__ . '/HttpIntegrationTestCase.php';

final class ApiDashboardHttpTest extends HttpIntegrationTestCase
{
    public function testDashboardBootstrapReturnsAllPrimaryWidgets(): void
    {
        $user = $this->createUser('super_admin');
        $this->authenticateUser($user);

        $response = $this->dispatchJson('GET', '/api/dashboard/bootstrap');

        self::assertSame(200, $response['status']);
        self::assertArrayHasKey('stats', $response['json']['data']);
        self::assertArrayHasKey('intake', $response['json']['data']['charts']);
        self::assertArrayHasKey('adoptions', $response['json']['data']['charts']);
        self::assertArrayHasKey('occupancy', $response['json']['data']['charts']);
        self::assertArrayHasKey('medical', $response['json']['data']['charts']);
        self::assertArrayHasKey('activity', $response['json']['data']);
    }

    public function testMedicalChartUsesTheSharedMetricsBundle(): void
    {
        $user = $this->createUser('super_admin');
        $this->authenticateUser($user);
        $_ENV['APP_PERFORMANCE_DEBUG'] = '1';

        $response = $this->dispatchJson('GET', '/api/dashboard/charts/medical');

        self::assertSame(200, $response['status']);
        self::assertArrayHasKey('labels', $response['json']['data']);
        self::assertArrayHasKey('datasets', $response['json']['data']);
        self::assertLessThanOrEqual(2, (int) ($response['headers']['X-App-Query-Count'] ?? PHP_INT_MAX));
    }
}

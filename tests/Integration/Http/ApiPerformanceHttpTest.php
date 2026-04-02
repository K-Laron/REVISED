<?php

declare(strict_types=1);

namespace Tests\Integration\Http;

require_once __DIR__ . '/HttpIntegrationTestCase.php';

final class ApiPerformanceHttpTest extends HttpIntegrationTestCase
{
    public function testDebugPerformanceHeadersAppearForAuthenticatedJsonRoutes(): void
    {
        $_ENV['APP_PERFORMANCE_DEBUG'] = '1';

        $user = $this->createUser('super_admin');
        $this->authenticateUser($user);

        $response = $this->dispatchJson('GET', '/api/dashboard/stats');

        self::assertSame(200, $response['status']);
        self::assertArrayHasKey('X-App-Query-Count', $response['headers']);
        self::assertArrayHasKey('X-App-Request-Time-Ms', $response['headers']);
        self::assertArrayHasKey('X-App-Database-Time-Ms', $response['headers']);
    }
}

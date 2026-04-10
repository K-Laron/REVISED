<?php

declare(strict_types=1);

namespace Tests\Controllers;

use App\Controllers\DashboardController;
use App\Core\Request;
use App\Core\Response;
use App\Services\DashboardService;
use App\Core\Session;
use Tests\TestCase;
use ReflectionClass;

final class DashboardControllerTest extends TestCase
{
    public function testDashboardPageLoadsLocalChartAssetInsteadOfJsDelivr(): void
    {
        // Mock session token for CSRF
        $this->container->get(Session::class)
            ->method('instanceGet')
            ->with('_csrf_token', '')
            ->willReturn('mock_csrf_token');

        $request = new Request(
            [
                'REQUEST_METHOD' => 'GET',
                'REQUEST_URI' => '/dashboard',
                'HTTP_ACCEPT' => 'text/html',
            ],
            [],
            [],
            [],
            []
        );
        $request->setAttribute('auth_user', [
            'id' => 1,
            'first_name' => 'Kenneth',
            'last_name' => 'Laron',
            'role_name' => 'super_admin',
            'role_display_name' => 'Super Admin',
            'permissions' => [],
        ]);

        $dashboardService = $this->createMock(DashboardService::class);
        $dashboardService->method('actionQueue')->willReturn([]);

        $response = (new DashboardController($dashboardService))->index($request);
        $content = $this->responseProperty($response, 'content');

        self::assertIsString($content);
        self::assertStringContainsString('/assets/vendor/chart.js/chart.umd.js', $content);
        self::assertStringNotContainsString('cdn.jsdelivr.net/npm/chart.js', $content);
    }

    private function responseProperty(Response $response, string $name): mixed
    {
        $reflection = new ReflectionClass($response);
        $property = $reflection->getProperty($name);

        return $property->getValue($response);
    }
}

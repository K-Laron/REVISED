<?php

declare(strict_types=1);

namespace Tests\Routes;

use PHPUnit\Framework\TestCase;

final class ApiRouteRegistrationTest extends TestCase
{
    public function testValidateTestRouteIsNotRegisteredWhenDebugModeIsDisabled(): void
    {
        $routes = $this->registeredRoutes(false);

        self::assertArrayNotHasKey('/api/validate-test', $routes['POST']);
    }

    public function testValidateTestRouteIsRegisteredWhenDebugModeIsEnabled(): void
    {
        $routes = $this->registeredRoutes(true);

        self::assertArrayHasKey('/api/validate-test', $routes['POST']);
    }

    public function testApiRoutesAreSplitIntoModuleFilesAndStillRegisterRepresentativeEndpoints(): void
    {
        $routeDirectory = dirname(__DIR__, 2) . '/routes/api';
        $expectedFiles = [
            'core.php',
            'system.php',
            'auth.php',
            'dashboard.php',
            'animals.php',
            'billing.php',
            'inventory.php',
            'kennels.php',
            'medical.php',
            'adoptions.php',
            'users.php',
            'roles.php',
            'reports.php',
            'notifications.php',
            'search.php',
        ];

        foreach ($expectedFiles as $file) {
            self::assertFileExists($routeDirectory . '/' . $file);
        }

        $routes = $this->registeredRoutes(true);

        self::assertCount(15, glob($routeDirectory . '/*.php'));
        self::assertCount(61, $routes['GET']);
        self::assertCount(39, $routes['POST']);
        self::assertCount(20, $routes['PUT']);
        self::assertCount(0, $routes['PATCH']);
        self::assertCount(7, $routes['DELETE']);

        self::assertArrayHasKey('/api/ping', $routes['GET']);
        self::assertArrayHasKey('/api/system/health', $routes['GET']);
        self::assertArrayHasKey('/api/auth/login', $routes['POST']);
        self::assertArrayHasKey('/api/dashboard/bootstrap', $routes['GET']);
        self::assertArrayHasKey('/api/animals', $routes['GET']);
        self::assertArrayHasKey('/api/animals/{id}/photos', $routes['POST']);
        self::assertArrayHasKey('/api/billing/invoices', $routes['POST']);
        self::assertArrayHasKey('/api/inventory', $routes['GET']);
        self::assertArrayHasKey('/api/kennels', $routes['GET']);
        self::assertArrayHasKey('/api/medical', $routes['GET']);
        self::assertArrayHasKey('/api/adoptions', $routes['GET']);
        self::assertArrayHasKey('/api/users', $routes['GET']);
        self::assertArrayHasKey('/api/roles', $routes['GET']);
        self::assertArrayHasKey('/api/reports/generate', $routes['GET']);
        self::assertArrayHasKey('/api/notifications', $routes['GET']);
        self::assertArrayHasKey('/api/search/global', $routes['GET']);
    }

    private function registeredRoutes(bool $debug): array
    {
        $_ENV['APP_DEBUG'] = $debug ? 'true' : 'false';
        $router = new class {
            public array $routes = [
                'GET' => [],
                'POST' => [],
                'PUT' => [],
                'PATCH' => [],
                'DELETE' => [],
            ];

            public function get(string $path, callable|string $handler, array $middleware = []): void
            {
                $this->routes['GET'][$path] = compact('handler', 'middleware');
            }

            public function post(string $path, callable|string $handler, array $middleware = []): void
            {
                $this->routes['POST'][$path] = compact('handler', 'middleware');
            }

            public function put(string $path, callable|string $handler, array $middleware = []): void
            {
                $this->routes['PUT'][$path] = compact('handler', 'middleware');
            }

            public function patch(string $path, callable|string $handler, array $middleware = []): void
            {
                $this->routes['PATCH'][$path] = compact('handler', 'middleware');
            }

            public function delete(string $path, callable|string $handler, array $middleware = []): void
            {
                $this->routes['DELETE'][$path] = compact('handler', 'middleware');
            }
        };

        require dirname(__DIR__, 2) . '/routes/api.php';

        return $router->routes;
    }
}

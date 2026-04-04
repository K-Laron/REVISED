<?php

declare(strict_types=1);

namespace Tests\Routes;

use PHPUnit\Framework\TestCase;

final class WebRouteRegistrationTest extends TestCase
{
    public function testRepresentativeWebRoutesRemainRegistered(): void
    {
        $routes = $this->registeredRoutes();

        self::assertArrayHasKey('/', $routes['GET']);
        self::assertArrayHasKey('/login', $routes['GET']);
        self::assertArrayHasKey('/forgot-password', $routes['GET']);
        self::assertArrayHasKey('/dashboard', $routes['GET']);
        self::assertArrayHasKey('/animals', $routes['GET']);
        self::assertArrayHasKey('/animals/{id}/edit', $routes['GET']);
        self::assertArrayHasKey('/adopt/apply', $routes['GET']);
        self::assertArrayHasKey('/search', $routes['GET']);
        self::assertCount(33, $routes['GET']);
        self::assertCount(1, $routes['POST']);
    }

    private function registeredRoutes(): array
    {
        $router = new class {
            public array $routes = ['GET' => [], 'POST' => []];

            public function get(string $path, callable|string $handler, array $middleware = []): void
            {
                $this->routes['GET'][$path] = compact('handler', 'middleware');
            }

            public function post(string $path, callable|string $handler, array $middleware = []): void
            {
                $this->routes['POST'][$path] = compact('handler', 'middleware');
            }
        };

        require dirname(__DIR__, 2) . '/routes/web.php';

        return $router->routes;
    }
}

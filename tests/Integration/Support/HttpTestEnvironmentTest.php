<?php

declare(strict_types=1);

namespace Tests\Integration\Support;

require_once __DIR__ . '/HttpTestEnvironment.php';

use App\Middleware\AuthMiddleware;
use App\Middleware\CorsMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\GuestMiddleware;
use App\Middleware\PermissionMiddleware;
use App\Middleware\RateLimitMiddleware;
use App\Middleware\RoleMiddleware;
use PHPUnit\Framework\TestCase;

final class HttpTestEnvironmentTest extends TestCase
{
    public function testRequestUriAndAppConfigAreConsistent(): void
    {
        $environment = new HttpTestEnvironment();

        self::assertSame('/reports?type=animals&page=2', $environment->requestUri('/reports', [
            'type' => 'animals',
            'page' => 2,
        ]));

        self::assertSame([
            'name' => 'Catarman Animal Shelter',
            'settings' => [],
            'middleware_aliases' => [
                'auth' => AuthMiddleware::class,
                'guest' => GuestMiddleware::class,
                'role' => RoleMiddleware::class,
                'perm' => PermissionMiddleware::class,
                'throttle' => RateLimitMiddleware::class,
                'cors' => CorsMiddleware::class,
                'csrf' => CsrfMiddleware::class,
            ],
        ], $environment->appConfig());
    }

    public function testHttpIntegrationTestCaseUsesSharedEnvironmentHelper(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 3) . '/tests/Integration/Http/HttpIntegrationTestCase.php');

        self::assertStringContainsString('HttpTestEnvironment', $source);
    }
}

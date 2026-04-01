<?php

declare(strict_types=1);

namespace Tests\Integration\Http;

require_once __DIR__ . '/../DatabaseIntegrationTestCase.php';

use App\Core\Request;
use App\Core\Router;
use App\Core\Session;
use App\Middleware\AuthMiddleware;
use App\Middleware\CorsMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\GuestMiddleware;
use App\Middleware\PermissionMiddleware;
use App\Middleware\RateLimitMiddleware;
use App\Middleware\RoleMiddleware;
use App\Models\User;
use Tests\Integration\DatabaseIntegrationTestCase;

abstract class HttpIntegrationTestCase extends DatabaseIntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $_SERVER = [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_USER_AGENT' => 'PHPUnit HTTP Test',
            'REQUEST_URI' => '/',
        ];
        $_GET = [];
        $_POST = [];
        $_COOKIE = [];

        if (session_status() !== PHP_SESSION_ACTIVE) {
            Session::start();
        }

        $_SESSION = [];
        $GLOBALS['app'] = $this->appConfig();
        header_remove();
        http_response_code(200);
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        header_remove();
        http_response_code(200);

        parent::tearDown();
    }

    protected function dispatchJson(string $method, string $uri, array $body = [], array $query = [], array $server = []): array
    {
        $requestUri = $this->requestUri($uri, $query);
        $_SERVER = array_merge([
            'REQUEST_METHOD' => strtoupper($method),
            'REQUEST_URI' => $requestUri,
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_USER_AGENT' => 'PHPUnit HTTP Test',
            'HTTP_ACCEPT' => 'application/json',
        ], $server);
        $_GET = $query;
        $_POST = [];
        $_COOKIE = [];
        $GLOBALS['app'] = $this->appConfig();
        header_remove();
        http_response_code(200);

        $request = new Request($_SERVER, $query, $body, [], $_COOKIE);

        $router = new Router();
        require dirname(__DIR__, 3) . '/routes/web.php';
        require dirname(__DIR__, 3) . '/routes/api.php';

        ob_start();
        $router->dispatch($request);
        $content = ob_get_clean() ?: '';

        return [
            'status' => http_response_code(),
            'content' => $content,
            'json' => $content !== '' ? json_decode($content, true, 512, JSON_THROW_ON_ERROR) : [],
        ];
    }

    protected function authenticateUser(array $user): array
    {
        $users = new User();
        $hydrated = $users->findById((int) $user['id']) ?: [];
        $hydrated['permissions'] = $users->permissions((int) $user['id']);
        unset($hydrated['password_hash']);

        $token = bin2hex(random_bytes(32));
        $users->storeSession(
            (int) $user['id'],
            hash('sha256', $token),
            '127.0.0.1',
            'PHPUnit HTTP Test',
            date('Y-m-d H:i:s', time() + 3600)
        );

        Session::put('auth.user', $hydrated);
        Session::put('auth.session_token', $token);

        return $hydrated;
    }

    protected function csrfToken(): string
    {
        return CsrfMiddleware::token();
    }

    private function requestUri(string $uri, array $query): string
    {
        if ($query === []) {
            return $uri;
        }

        return $uri . '?' . http_build_query($query);
    }

    private function appConfig(): array
    {
        return [
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
        ];
    }
}

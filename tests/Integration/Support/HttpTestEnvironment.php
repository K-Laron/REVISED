<?php

declare(strict_types=1);

namespace Tests\Integration\Support;

use App\Core\Response;
use App\Core\Session;
use App\Middleware\AuthMiddleware;
use App\Middleware\CorsMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\GuestMiddleware;
use App\Middleware\PermissionMiddleware;
use App\Middleware\RateLimitMiddleware;
use App\Middleware\RoleMiddleware;

final class HttpTestEnvironment
{
    public function ensureSessionStarted(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            Session::start();
        }
    }

    public function resetGlobals(string $requestUri = '/'): void
    {
        $_SERVER = [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_USER_AGENT' => 'PHPUnit HTTP Test',
            'REQUEST_URI' => $requestUri,
        ];
        $_GET = [];
        $_POST = [];
        $_COOKIE = [];
    }

    public function primeJsonRequest(string $method, string $uri, array $query = [], array $server = []): array
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

        return $_SERVER;
    }

    public function resetResponseState(): void
    {
        Response::resetSentHeaders();
        header_remove();
        http_response_code(200);
    }

    public function appConfig(): array
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

    public function requestUri(string $uri, array $query): string
    {
        if ($query === []) {
            return $uri;
        }

        return $uri . '?' . http_build_query($query);
    }

    public function capturedHeaders(): array
    {
        $headers = Response::sentHeaders();

        if ($headers !== []) {
            return $headers;
        }

        foreach (headers_list() as $headerLine) {
            if (!str_contains($headerLine, ':')) {
                continue;
            }

            [$name, $value] = array_map('trim', explode(':', $headerLine, 2));
            $headers[$name] = $value;
        }

        return $headers;
    }
}

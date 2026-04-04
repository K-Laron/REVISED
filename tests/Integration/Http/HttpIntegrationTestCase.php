<?php

declare(strict_types=1);

namespace Tests\Integration\Http;

require_once __DIR__ . '/../DatabaseIntegrationTestCase.php';
require_once __DIR__ . '/../Support/HttpTestEnvironment.php';

use App\Core\Request;
use App\Core\Router;
use App\Middleware\CsrfMiddleware;
use App\Models\User;
use Tests\Integration\DatabaseIntegrationTestCase;
use Tests\Integration\Support\HttpTestEnvironment;

abstract class HttpIntegrationTestCase extends DatabaseIntegrationTestCase
{
    private ?HttpTestEnvironment $environment = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->environment()->resetGlobals();
        $this->environment()->ensureSessionStarted();

        $_SESSION = [];
        unset($_ENV['APP_PERFORMANCE_DEBUG']);
        $GLOBALS['app'] = $this->environment()->appConfig();
        $this->environment()->resetResponseState();
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        unset($_ENV['APP_PERFORMANCE_DEBUG']);
        $this->environment()->resetResponseState();

        parent::tearDown();
    }

    protected function dispatchJson(string $method, string $uri, array $body = [], array $query = [], array $server = []): array
    {
        $_SERVER = $this->environment()->primeJsonRequest($method, $uri, $query, $server);
        $GLOBALS['app'] = $this->environment()->appConfig();
        $this->environment()->resetResponseState();

        $request = new Request($_SERVER, $query, $body, [], $_COOKIE);

        $router = new Router();
        require dirname(__DIR__, 3) . '/routes/web.php';
        require dirname(__DIR__, 3) . '/routes/api.php';

        ob_start();
        $router->dispatch($request);
        $content = ob_get_clean() ?: '';
        $headers = $this->environment()->capturedHeaders();

        return [
            'status' => http_response_code(),
            'content' => $content,
            'json' => $this->shouldDecodeJson($headers, (string) ($_SERVER['HTTP_ACCEPT'] ?? 'application/json'))
                ? ($content !== '' ? json_decode($content, true, 512, JSON_THROW_ON_ERROR) : [])
                : [],
            'headers' => $headers,
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

        \App\Core\Session::put('auth.user', $hydrated);
        \App\Core\Session::put('auth.session_token', $token);

        return $hydrated;
    }

    protected function csrfToken(): string
    {
        return CsrfMiddleware::token();
    }

    private function environment(): HttpTestEnvironment
    {
        if ($this->environment === null) {
            $this->environment = new HttpTestEnvironment();
        }

        return $this->environment;
    }

    /**
     * The page smoke tests reuse this helper to route HTML responses through the app.
     * Only attempt JSON decoding when the response or request explicitly indicates JSON.
     *
     * @param array<string, string> $headers
     */
    private function shouldDecodeJson(array $headers, string $acceptHeader): bool
    {
        $contentType = strtolower((string) ($headers['Content-Type'] ?? ''));
        $normalizedAccept = strtolower($acceptHeader);

        if ($contentType !== '') {
            return str_contains($contentType, 'application/json') || str_contains($contentType, '+json');
        }

        return str_contains($normalizedAccept, 'application/json') || str_contains($normalizedAccept, '+json');
    }
}

<?php

declare(strict_types=1);

namespace Tests\Integration\Http;

require_once __DIR__ . '/HttpIntegrationTestCase.php';

final class ApiAuthHttpTest extends HttpIntegrationTestCase
{
    public function testLoginRouteAuthenticatesValidCredentialsThroughTheRouter(): void
    {
        $user = $this->createUser('super_admin');
        $token = $this->csrfToken();

        $response = $this->dispatchJson('POST', '/api/auth/login', [
            'login' => (string) $user['email'],
            'password' => 'IntegrationPass!123',
            '_token' => $token,
        ]);

        self::assertSame(200, $response['status']);
        self::assertTrue($response['json']['success']);
        self::assertSame((int) $user['id'], $response['json']['data']['user']['id']);
        self::assertIsArray($_SESSION['auth.user'] ?? null);
    }

    public function testLoginRouteRejectsMissingCsrfTokenBeforeAuthenticationLogicRuns(): void
    {
        $response = $this->dispatchJson('POST', '/api/auth/login', [
            'login' => 'missing@example.test',
            'password' => 'IntegrationPass!123',
        ]);

        self::assertSame(419, $response['status']);
        self::assertFalse($response['json']['success']);
        self::assertSame('CSRF_TOKEN_MISMATCH', $response['json']['error']['code']);
    }
}

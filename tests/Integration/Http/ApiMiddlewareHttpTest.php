<?php

declare(strict_types=1);

namespace Tests\Integration\Http;

require_once __DIR__ . '/HttpIntegrationTestCase.php';

final class ApiMiddlewareHttpTest extends HttpIntegrationTestCase
{
    public function testPermissionProtectedRouteReturnsForbiddenForAuthenticatedUserWithoutRequiredPermission(): void
    {
        $user = $this->createUser('adopter');
        $this->authenticateUser($user);

        $response = $this->dispatchJson('GET', '/api/animals');

        self::assertSame(403, $response['status']);
        self::assertFalse($response['json']['success']);
        self::assertSame('FORBIDDEN', $response['json']['error']['code']);
    }

    public function testThrottleMiddlewareReturnsRateLimitedAfterConfiguredAttemptCount(): void
    {
        $token = $this->csrfToken();

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $response = $this->dispatchJson('POST', '/api/auth/login', [
                'login' => 'missing@example.test',
                'password' => 'IntegrationPass!123',
                '_token' => $token,
            ]);

            self::assertSame(401, $response['status']);
        }

        $limited = $this->dispatchJson('POST', '/api/auth/login', [
            'login' => 'missing@example.test',
            'password' => 'IntegrationPass!123',
            '_token' => $token,
        ]);

        self::assertSame(429, $limited['status']);
        self::assertFalse($limited['json']['success']);
        self::assertSame('RATE_LIMITED', $limited['json']['error']['code']);
    }
}

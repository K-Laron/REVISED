<?php

declare(strict_types=1);

namespace Tests\Integration\Http;

require_once __DIR__ . '/HttpIntegrationTestCase.php';

final class ApiSystemHttpTest extends HttpIntegrationTestCase
{
    public function testRestoreRouteRequiresExactTypedConfirmationBeforeAnyRestoreAttempt(): void
    {
        $user = $this->createUser('super_admin');
        $this->authenticateUser($user);
        $token = $this->csrfToken();

        $response = $this->dispatchJson('POST', '/api/system/backups/42/restore', [
            'restore_confirmation' => 'RESTORE 41',
            '_token' => $token,
        ]);

        self::assertSame(422, $response['status']);
        self::assertFalse($response['json']['success']);
        self::assertSame('VALIDATION_ERROR', $response['json']['error']['code']);
        self::assertSame('Backup restore requires an exact typed confirmation.', $response['json']['error']['message']);
    }
}

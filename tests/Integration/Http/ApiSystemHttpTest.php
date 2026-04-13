<?php

declare(strict_types=1);

namespace Tests\Integration\Http;

require_once __DIR__ . '/HttpIntegrationTestCase.php';

final class ApiSystemHttpTest extends HttpIntegrationTestCase
{
    public function testHealthRouteReturnsSystemSnapshot(): void
    {
        $response = $this->dispatchJson('GET', '/api/system/health');

        self::assertSame(200, $response['status']);
        self::assertTrue($response['json']['success']);
        self::assertSame('ok', $response['json']['data']['status'] ?? null);
        self::assertArrayHasKey('database', $response['json']['data']);
    }

    public function testBackupsRouteReturnsPaginatedResultsForSuperAdmin(): void
    {
        $user = $this->createUser('super_admin');
        $this->authenticateUser($user);

        \App\Core\Database::execute(
            'INSERT INTO system_backups (
                backup_type, file_path, file_size_bytes, checksum_sha256, status, tables_included,
                error_message, started_at, completed_at, created_by, restored_by, restored_at
             ) VALUES (
                :backup_type, :file_path, :file_size_bytes, :checksum_sha256, :status, :tables_included,
                :error_message, :started_at, :completed_at, :created_by, :restored_by, :restored_at
             )',
            [
                'backup_type' => 'full',
                'file_path' => 'storage/backups/test-backup.sql.gz',
                'file_size_bytes' => 1024,
                'checksum_sha256' => str_repeat('a', 64),
                'status' => 'completed',
                'tables_included' => json_encode(['animals'], JSON_THROW_ON_ERROR),
                'error_message' => null,
                'started_at' => date('Y-m-d H:i:s', strtotime('-10 minutes')),
                'completed_at' => date('Y-m-d H:i:s', strtotime('-9 minutes')),
                'created_by' => $user['id'],
                'restored_by' => null,
                'restored_at' => null,
            ]
        );

        $response = $this->dispatchJson('GET', '/api/system/backups', [], ['page' => 1, 'per_page' => 10]);

        self::assertSame(200, $response['status']);
        self::assertTrue($response['json']['success']);
        self::assertGreaterThanOrEqual(1, (int) ($response['json']['meta']['total'] ?? 0));

        $items = is_array($response['json']['data'] ?? null) ? $response['json']['data'] : [];
        $filePaths = array_map(
            static fn (array $item): string => (string) ($item['file_path'] ?? ''),
            $items
        );

        self::assertContains('storage/backups/test-backup.sql.gz', $filePaths);
    }

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

<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class SystemBackup
{
    public function create(array $data): int
    {
        Database::execute(
            'INSERT INTO system_backups (
                backup_type,
                file_path,
                file_size_bytes,
                checksum_sha256,
                status,
                tables_included,
                error_message,
                started_at,
                completed_at,
                created_by,
                restored_by,
                restored_at
            ) VALUES (
                :backup_type,
                :file_path,
                :file_size_bytes,
                :checksum_sha256,
                :status,
                :tables_included,
                :error_message,
                :started_at,
                :completed_at,
                :created_by,
                :restored_by,
                :restored_at
            )',
            [
                'backup_type' => $data['backup_type'],
                'file_path' => $data['file_path'],
                'file_size_bytes' => $data['file_size_bytes'] ?? 0,
                'checksum_sha256' => $data['checksum_sha256'] ?? '',
                'status' => $data['status'],
                'tables_included' => $data['tables_included'],
                'error_message' => $data['error_message'] ?? null,
                'started_at' => $data['started_at'],
                'completed_at' => $data['completed_at'] ?? null,
                'created_by' => $data['created_by'] ?? null,
                'restored_by' => $data['restored_by'] ?? null,
                'restored_at' => $data['restored_at'] ?? null,
            ]
        );

        return (int) Database::lastInsertId();
    }

    public function find(int $backupId): array|false
    {
        $row = Database::fetch(
            'SELECT sb.*,
                    CONCAT(cb.first_name, " ", cb.last_name) AS created_by_name,
                    CONCAT(rb.first_name, " ", rb.last_name) AS restored_by_name
             FROM system_backups sb
             LEFT JOIN users cb ON cb.id = sb.created_by
             LEFT JOIN users rb ON rb.id = sb.restored_by
             WHERE sb.id = :id
             LIMIT 1',
            ['id' => $backupId]
        );

        return $row === false ? false : $this->normalize($row);
    }

    public function paginate(int $page, int $perPage): array
    {
        $offset = ($page - 1) * $perPage;
        $total = (int) (Database::fetch('SELECT COUNT(*) AS aggregate FROM system_backups')['aggregate'] ?? 0);
        $items = Database::fetchAll(
            'SELECT sb.*,
                    CONCAT(cb.first_name, " ", cb.last_name) AS created_by_name,
                    CONCAT(rb.first_name, " ", rb.last_name) AS restored_by_name
             FROM system_backups sb
             LEFT JOIN users cb ON cb.id = sb.created_by
             LEFT JOIN users rb ON rb.id = sb.restored_by
             ORDER BY sb.started_at DESC, sb.id DESC
             LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset
        );

        return [
            'items' => array_map(fn (array $row): array => $this->normalize($row), $items),
            'total' => $total,
        ];
    }

    public function markCompleted(int $backupId, int $fileSizeBytes, string $checksum): void
    {
        Database::execute(
            'UPDATE system_backups
             SET file_size_bytes = :file_size_bytes,
                 checksum_sha256 = :checksum_sha256,
                 status = :status,
                 completed_at = NOW(),
                 error_message = NULL
             WHERE id = :id',
            [
                'id' => $backupId,
                'file_size_bytes' => $fileSizeBytes,
                'checksum_sha256' => $checksum,
                'status' => 'completed',
            ]
        );
    }

    public function markFailed(int $backupId, string $message): void
    {
        Database::execute(
            'UPDATE system_backups
             SET status = :status,
                 error_message = :error_message,
                 completed_at = NOW()
             WHERE id = :id',
            [
                'id' => $backupId,
                'status' => 'failed',
                'error_message' => mb_substr($message, 0, 1000),
            ]
        );
    }

    public function markRestored(int $backupId, int $userId): void
    {
        Database::execute(
            'UPDATE system_backups
             SET restored_at = NOW(),
                 restored_by = :restored_by
             WHERE id = :id',
            ['id' => $backupId, 'restored_by' => $userId]
        );
    }

    private function normalize(array $row): array
    {
        $row['tables_included'] = $row['tables_included']
            ? (json_decode((string) $row['tables_included'], true) ?: [])
            : [];

        return $row;
    }
}

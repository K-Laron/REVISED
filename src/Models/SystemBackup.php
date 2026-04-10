<?php

declare(strict_types=1);

namespace App\Models;

class SystemBackup extends BaseModel
{
    protected static string $table = 'system_backups';
    protected static bool $useSoftDeletes = false; // Backup records are immutable

    public function find(int|string $id, bool $includeDeleted = false): array|false
    {
        $row = $this->db->fetch(
            'SELECT sb.*,
                    CONCAT(cb.first_name, " ", cb.last_name) AS created_by_name,
                    CONCAT(rb.first_name, " ", rb.last_name) AS restored_by_name
             FROM system_backups sb
             LEFT JOIN users cb ON cb.id = sb.created_by
             LEFT JOIN users rb ON rb.id = sb.restored_by
             WHERE sb.id = :id
             LIMIT 1',
            ['id' => $id]
        );

        return $row === false ? false : $this->normalize($row);
    }

    public function paginateBackups(int $page, int $perPage): array
    {
        $offset = ($page - 1) * $perPage;
        $total = (int) ($this->db->fetch('SELECT COUNT(*) AS aggregate FROM system_backups')['aggregate'] ?? 0);
        $items = $this->db->fetchAll(
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
        $this->update($backupId, [
            'file_size_bytes' => $fileSizeBytes,
            'checksum_sha256' => $checksum,
            'status' => 'completed',
            'completed_at' => date('Y-m-d H:i:s'),
            'error_message' => null
        ]);
    }

    public function markFailed(int $backupId, string $message): void
    {
        $this->update($backupId, [
            'status' => 'failed',
            'error_message' => mb_substr($message, 0, 1000),
            'completed_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function markRestored(int $backupId, int $userId): void
    {
        $this->update($backupId, [
            'restored_at' => date('Y-m-d H:i:s'),
            'restored_by' => $userId
        ]);
    }

    private function normalize(array $row): array
    {
        $row['tables_included'] = ($row['tables_included'] ?? null)
            ? (json_decode((string) $row['tables_included'], true) ?: [])
            : [];

        return $row;
    }
}

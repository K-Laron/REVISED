<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Pagination\PaginatedWindow;

class AuditLog extends BaseModel
{
    protected static string $table = 'audit_logs';
    protected static bool $useSoftDeletes = false; // Audit logs are immutable

    public function paginate(array $filters, int $page, int $perPage): array
    {
        $offset = ($page - 1) * $perPage;
        $conditions = [];
        $bindings = [];

        if (($filters['module'] ?? '') !== '') {
            $conditions[] = 'al.module = :module';
            $bindings['module'] = $filters['module'];
        }

        if (($filters['action'] ?? '') !== '') {
            $conditions[] = 'al.action = :action';
            $bindings['action'] = $filters['action'];
        }

        if (($filters['user_id'] ?? '') !== '') {
            $conditions[] = 'al.user_id = :user_id';
            $bindings['user_id'] = (int) $filters['user_id'];
        }

        if (($filters['record_table'] ?? '') !== '') {
            $conditions[] = 'al.record_table = :record_table';
            $bindings['record_table'] = $filters['record_table'];
        }

        if (($filters['start_date'] ?? '') !== '') {
            $conditions[] = 'al.created_at >= :start_date';
            $bindings['start_date'] = $filters['start_date'] . ' 00:00:00';
        }

        if (($filters['end_date'] ?? '') !== '') {
            $conditions[] = 'al.created_at <= :end_date';
            $bindings['end_date'] = $filters['end_date'] . ' 23:59:59';
        }

        $where = $conditions === [] ? '' : 'WHERE ' . implode(' AND ', $conditions);
        $countSql = 'SELECT COUNT(*) AS aggregate FROM audit_logs al ' . $where;
        $itemsSql = 'SELECT al.*, CONCAT(u.first_name, " ", u.last_name) AS user_name
                     FROM audit_logs al
                     LEFT JOIN users u ON u.id = al.user_id
                     ' . $where . '
                     ORDER BY al.created_at DESC, al.id DESC
                     LIMIT ' . (int) ($perPage + 1) . ' OFFSET ' . (int) $offset;

        $window = PaginatedWindow::resolve(
            $this->db->fetchAll($itemsSql, $bindings),
            $page,
            $perPage,
            fn (): int => (int) (($this->db->fetch($countSql, $bindings)['aggregate'] ?? 0))
        );
        $items = $window['items'];

        foreach ($items as &$item) {
            $item['old_values'] = $item['old_values'] ? json_decode((string) $item['old_values'], true) : [];
            $item['new_values'] = $item['new_values'] ? json_decode((string) $item['new_values'], true) : [];
        }

        return [
            'items' => $items,
            'total' => $window['total'],
        ];
    }

    public function recent(int $limit): array
    {
        return $this->db->fetchAll(
            'SELECT action, module, record_id, created_at
             FROM audit_logs
             ORDER BY created_at DESC
             LIMIT ' . max(1, (int) $limit)
        );
    }

    public function listForAnimalDossier(int $animalId): array
    {
        return $this->db->fetchAll(
            'SELECT al.*, CONCAT(u.first_name, " ", u.last_name) AS user_name
             FROM audit_logs al
             LEFT JOIN users u ON u.id = al.user_id
             WHERE (al.record_table = "animals" AND al.record_id = :animal_id)
                OR (al.record_table = "animal_photos" AND al.record_id = :animal_photo_record_id)
             ORDER BY al.created_at DESC, al.id DESC',
            ['animal_id' => $animalId, 'animal_photo_record_id' => $animalId]
        );
    }

    public function record(
        ?int $userId,
        string $action,
        string $module,
        ?string $recordTable,
        int|string|null $recordId,
        array $oldValues,
        array $newValues,
        ?string $ipAddress,
        ?string $userAgent
    ): void {
        $this->db->execute(
            'INSERT INTO audit_logs (user_id, action, module, record_table, record_id, old_values, new_values, ip_address, user_agent, request_id)
             VALUES (:user_id, :action, :module, :record_table, :record_id, :old_values, :new_values, :ip_address, :user_agent, :request_id)',
            [
                'user_id' => $userId,
                'action' => $action,
                'module' => $module,
                'record_table' => $recordTable,
                'record_id' => $recordId !== null ? (int) $recordId : null,
                'old_values' => $oldValues === [] ? null : json_encode($oldValues, JSON_UNESCAPED_SLASHES),
                'new_values' => $newValues === [] ? null : json_encode($newValues, JSON_UNESCAPED_SLASHES),
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent ? mb_substr($userAgent, 0, 500) : null,
                'request_id' => bin2hex(random_bytes(16)),
            ]
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class AuditLog
{
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
                     LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset;

        $total = (int) ((Database::fetch($countSql, $bindings)['aggregate'] ?? 0));
        $items = Database::fetchAll($itemsSql, $bindings);

        foreach ($items as &$item) {
            $item['old_values'] = $item['old_values'] ? json_decode((string) $item['old_values'], true) : [];
            $item['new_values'] = $item['new_values'] ? json_decode((string) $item['new_values'], true) : [];
        }

        return [
            'items' => $items,
            'total' => $total,
        ];
    }
}

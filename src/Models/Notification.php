<?php

declare(strict_types=1);

namespace App\Models;

class Notification extends BaseModel
{
    protected static string $table = 'notifications';
    protected static bool $useSoftDeletes = false; // System notifications don't typically use soft deletes here

    public function paginateForUser(int $userId, int $page, int $perPage): array
    {
        $offset = ($page - 1) * $perPage;
        $rows = $this->db->fetchAll(
            'SELECT *
             FROM notifications
             WHERE user_id = :user_id
             ORDER BY created_at DESC, id DESC
             LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset,
            ['user_id' => $userId]
        );

        foreach ($rows as &$row) {
            $row['is_read'] = (int) ($row['is_read'] ?? 0) === 1;
        }

        return $rows;
    }

    public function countForUser(int $userId): int
    {
        $row = $this->db->fetch(
            'SELECT COUNT(*) AS aggregate FROM notifications WHERE user_id = :user_id',
            ['user_id' => $userId]
        );

        return (int) ($row['aggregate'] ?? 0);
    }

    public function unreadCount(int $userId): int
    {
        $row = $this->db->fetch(
            'SELECT COUNT(*) AS aggregate
             FROM notifications
             WHERE user_id = :user_id
               AND is_read = 0',
            ['user_id' => $userId]
        );

        return (int) ($row['aggregate'] ?? 0);
    }

    public function findForUser(int $notificationId, int $userId): array|false
    {
        $row = $this->db->fetch(
            'SELECT *
             FROM notifications
             WHERE id = :id
               AND user_id = :user_id
             LIMIT 1',
            ['id' => $notificationId, 'user_id' => $userId]
        );

        if ($row !== false) {
            $row['is_read'] = (int) ($row['is_read'] ?? 0) === 1;
        }

        return $row;
    }

    public function markRead(int $notificationId, int $userId): void
    {
        $this->db->execute(
            'UPDATE notifications
             SET is_read = 1,
                 read_at = COALESCE(read_at, NOW())
             WHERE id = :id
               AND user_id = :user_id',
            ['id' => $notificationId, 'user_id' => $userId]
        );
    }

    public function markAllRead(int $userId): void
    {
        $this->db->execute(
            'UPDATE notifications
             SET is_read = 1,
                 read_at = COALESCE(read_at, NOW())
             WHERE user_id = :user_id
               AND is_read = 0',
            ['user_id' => $userId]
        );
    }
}

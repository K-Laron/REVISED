<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Notification;
use RuntimeException;

class NotificationService
{
    private Notification $notifications;

    public function __construct()
    {
        $this->notifications = new Notification();
    }

    public function list(int $userId, int $page, int $perPage): array
    {
        return [
            'items' => $this->notifications->paginateForUser($userId, $page, $perPage),
            'total' => $this->notifications->countForUser($userId),
        ];
    }

    public function unreadCount(int $userId): int
    {
        return $this->notifications->unreadCount($userId);
    }

    public function markRead(int $notificationId, int $userId): array
    {
        $notification = $this->notifications->findForUser($notificationId, $userId);
        if ($notification === false) {
            throw new RuntimeException('Notification not found.');
        }

        $this->notifications->markRead($notificationId, $userId);

        return $this->notifications->findForUser($notificationId, $userId) ?: $notification;
    }

    public function markAllRead(int $userId): void
    {
        $this->notifications->markAllRead($userId);
    }

    public function create(array $data): int
    {
        return $this->notifications->create([
            'user_id' => (int) $data['user_id'],
            'type' => (string) $data['type'],
            'title' => (string) $data['title'],
            'message' => (string) $data['message'],
            'link' => $data['link'] ?? null,
            'is_read' => 0,
            'read_at' => null,
        ]);
    }
}

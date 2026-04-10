<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\Concerns\InteractsWithApi;
use App\Core\Request;
use App\Core\Response;
use App\Services\NotificationService;
use RuntimeException;

class NotificationController
{
    use InteractsWithApi;

    public function __construct(
        private readonly NotificationService $notifications
    ) {
    }

    public function list(Request $request): Response
    {
        $page = max(1, (int) $request->query('page', 1));
        $perPage = max(1, min(50, (int) $request->query('per_page', 10)));
        $userId = $this->currentUserId($request);
        $result = $this->notifications->list($userId, $page, $perPage);

        return Response::success(
            $result['items'],
            'Notifications retrieved successfully.',
            [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $result['total'],
                'unread_count' => $this->notifications->unreadCount($userId),
            ]
        );
    }

    public function unreadCount(Request $request): Response
    {
        return Response::success([
            'count' => $this->notifications->unreadCount($this->currentUserId($request)),
        ], 'Unread notification count retrieved successfully.');
    }

    public function markRead(Request $request, string $id): Response
    {
        try {
            $notification = $this->notifications->markRead((int) $id, $this->currentUserId($request));
        } catch (RuntimeException $exception) {
            return Response::error(404, 'NOT_FOUND', $exception->getMessage());
        }

        return Response::success($notification, 'Notification marked as read.');
    }

    public function markAllRead(Request $request): Response
    {
        $this->notifications->markAllRead($this->currentUserId($request));

        return Response::success([], 'All notifications marked as read.');
    }
}

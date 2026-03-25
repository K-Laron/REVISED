<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\NotificationService;
use RuntimeException;

class NotificationController
{
    private NotificationService $notifications;

    public function __construct()
    {
        $this->notifications = new NotificationService();
    }

    public function list(Request $request): Response
    {
        $authUser = $request->attribute('auth_user');
        $page = max(1, (int) $request->query('page', 1));
        $perPage = max(1, min(50, (int) $request->query('per_page', 10)));
        $result = $this->notifications->list((int) $authUser['id'], $page, $perPage);

        return Response::success(
            $result['items'],
            'Notifications retrieved successfully.',
            [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $result['total'],
                'unread_count' => $this->notifications->unreadCount((int) $authUser['id']),
            ]
        );
    }

    public function unreadCount(Request $request): Response
    {
        $authUser = $request->attribute('auth_user');

        return Response::success([
            'count' => $this->notifications->unreadCount((int) $authUser['id']),
        ], 'Unread notification count retrieved successfully.');
    }

    public function markRead(Request $request, string $id): Response
    {
        $authUser = $request->attribute('auth_user');

        try {
            $notification = $this->notifications->markRead((int) $id, (int) $authUser['id']);
        } catch (RuntimeException $exception) {
            return Response::error(404, 'NOT_FOUND', $exception->getMessage());
        }

        return Response::success($notification, 'Notification marked as read.');
    }

    public function markAllRead(Request $request): Response
    {
        $authUser = $request->attribute('auth_user');
        $this->notifications->markAllRead((int) $authUser['id']);

        return Response::success([], 'All notifications marked as read.');
    }
}

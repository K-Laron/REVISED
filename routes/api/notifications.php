<?php

declare(strict_types=1);

$router->get('/api/notifications', \App\Controllers\NotificationController::class . '@list', ['cors', 'auth']);
$router->get('/api/notifications/unread-count', \App\Controllers\NotificationController::class . '@unreadCount', ['cors', 'auth']);
$router->put('/api/notifications/{id}/read', \App\Controllers\NotificationController::class . '@markRead', ['cors', 'csrf', 'auth']);
$router->put('/api/notifications/read-all', \App\Controllers\NotificationController::class . '@markAllRead', ['cors', 'csrf', 'auth']);

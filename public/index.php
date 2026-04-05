<?php

declare(strict_types=1);

use App\Support\Routing\RouteCatalog;

require_once __DIR__ . '/../bootstrap/app.php';

$request = App\Core\Request::capture();

if (App\Core\ExceptionHandler::inMaintenanceMode()) {
    $maintenanceAllowlist = [
        '/login',
        '/api/auth/login',
        '/api/system/health',
        '/settings',
        '/api/system/settings',
        '/api/system/maintenance',
        '/api/system/readiness',
        '/api/system/backups',
    ];

    if (!in_array($request->path(), $maintenanceAllowlist, true)) {
        App\Core\ExceptionHandler::maintenanceResponse($request)->send();
        return;
    }
}

$router = new App\Core\Router();
(new RouteCatalog())->register($router);

$router->dispatch($request);

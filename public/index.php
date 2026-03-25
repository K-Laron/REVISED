<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

require_once __DIR__ . '/../config/app.php';

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

require_once __DIR__ . '/../routes/web.php';
require_once __DIR__ . '/../routes/api.php';

$router->dispatch($request);

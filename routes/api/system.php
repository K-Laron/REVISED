<?php

declare(strict_types=1);

$router->get('/api/system/health', \App\Controllers\SystemController::class . '@health', ['cors']);
$router->get('/api/system/settings', \App\Controllers\SystemController::class . '@settings', ['cors', 'auth']);
$router->put('/api/system/settings', \App\Controllers\SystemController::class . '@updateSettings', ['cors', 'csrf', 'auth', 'role:super_admin']);
$router->put('/api/system/maintenance', \App\Controllers\SystemController::class . '@updateMaintenance', ['cors', 'csrf', 'auth', 'role:super_admin']);
$router->get('/api/system/readiness', \App\Controllers\SystemController::class . '@readiness', ['cors', 'auth']);
$router->post('/api/system/backup', \App\Controllers\SystemController::class . '@createBackup', ['cors', 'csrf', 'auth', 'role:super_admin']);
$router->get('/api/system/backups', \App\Controllers\SystemController::class . '@listBackups', ['cors', 'auth', 'role:super_admin']);
$router->post('/api/system/backups/{id}/restore', \App\Controllers\SystemController::class . '@restoreBackup', ['cors', 'csrf', 'auth', 'role:super_admin']);

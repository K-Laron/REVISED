<?php

declare(strict_types=1);

$router->get('/api/roles', \App\Controllers\RoleController::class . '@list', ['cors', 'auth', 'perm:users.read']);
$router->get('/api/roles/{id}/permissions', \App\Controllers\RoleController::class . '@permissions', ['cors', 'auth', 'role:super_admin']);
$router->put('/api/roles/{id}/permissions', \App\Controllers\RoleController::class . '@updatePermissions', ['cors', 'csrf', 'auth', 'role:super_admin']);

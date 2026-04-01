<?php

declare(strict_types=1);

$router->get('/api/kennels', \App\Controllers\KennelController::class . '@list', ['cors', 'auth', 'perm:kennels.read']);
$router->post('/api/kennels', \App\Controllers\KennelController::class . '@store', ['cors', 'csrf', 'auth', 'perm:kennels.create']);
$router->put('/api/kennels/{id}', \App\Controllers\KennelController::class . '@update', ['cors', 'csrf', 'auth', 'perm:kennels.update']);
$router->delete('/api/kennels/{id}', \App\Controllers\KennelController::class . '@destroy', ['cors', 'csrf', 'auth', 'perm:kennels.delete']);
$router->post('/api/kennels/{id}/assign', \App\Controllers\KennelController::class . '@assignAnimal', ['cors', 'csrf', 'auth', 'perm:kennels.update']);
$router->post('/api/kennels/{id}/release', \App\Controllers\KennelController::class . '@releaseAnimal', ['cors', 'csrf', 'auth', 'perm:kennels.update']);
$router->get('/api/kennels/{id}/history', \App\Controllers\KennelController::class . '@history', ['cors', 'auth', 'perm:kennels.read']);
$router->get('/api/kennels/stats', \App\Controllers\KennelController::class . '@stats', ['cors', 'auth', 'perm:kennels.read']);
$router->post('/api/kennels/{id}/maintenance', \App\Controllers\KennelController::class . '@logMaintenance', ['cors', 'csrf', 'auth', 'perm:kennels.update']);
$router->get('/api/kennels/{id}/maintenance', \App\Controllers\KennelController::class . '@maintenanceHistory', ['cors', 'auth', 'perm:kennels.read']);

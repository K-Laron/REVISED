<?php

declare(strict_types=1);

$router->get('/api/users', \App\Controllers\UserController::class . '@list', ['cors', 'auth', 'perm:users.read']);
$router->post('/api/users', \App\Controllers\UserController::class . '@store', ['cors', 'csrf', 'auth', 'perm:users.create']);
$router->get('/api/users/{id}', \App\Controllers\UserController::class . '@get', ['cors', 'auth', 'perm:users.read']);
$router->put('/api/users/{id}', \App\Controllers\UserController::class . '@update', ['cors', 'csrf', 'auth', 'perm:users.update']);
$router->delete('/api/users/{id}', \App\Controllers\UserController::class . '@destroy', ['cors', 'csrf', 'auth', 'perm:users.delete']);
$router->post('/api/users/{id}/restore', \App\Controllers\UserController::class . '@restore', ['cors', 'csrf', 'auth', 'perm:users.delete']);
$router->put('/api/users/{id}/role', \App\Controllers\UserController::class . '@changeRole', ['cors', 'csrf', 'auth', 'perm:users.update']);
$router->post('/api/users/{id}/reset-password', \App\Controllers\UserController::class . '@resetPassword', ['cors', 'csrf', 'auth', 'perm:users.update']);
$router->get('/api/users/{id}/sessions', \App\Controllers\UserController::class . '@sessions', ['cors', 'auth', 'perm:users.read']);
$router->delete('/api/users/{id}/sessions/{sessionId}', \App\Controllers\UserController::class . '@destroySession', ['cors', 'csrf', 'auth', 'perm:users.update']);

<?php

declare(strict_types=1);

$router->post('/api/auth/login', \App\Controllers\AuthController::class . '@login', ['throttle:5', 'cors', 'csrf', 'guest']);
$router->post('/api/auth/logout', \App\Controllers\AuthController::class . '@logout', ['cors', 'csrf', 'auth']);
$router->post('/api/auth/forgot-password', \App\Controllers\AuthController::class . '@forgotPassword', ['throttle:3', 'cors', 'csrf', 'guest']);
$router->post('/api/auth/reset-password', \App\Controllers\AuthController::class . '@resetPassword', ['throttle:3', 'cors', 'csrf', 'guest']);
$router->get('/api/auth/me', \App\Controllers\AuthController::class . '@me', ['cors', 'auth']);
$router->put('/api/auth/profile', \App\Controllers\AuthController::class . '@updateProfile', ['cors', 'csrf', 'auth']);
$router->put('/api/auth/change-password', \App\Controllers\AuthController::class . '@changePassword', ['cors', 'csrf', 'auth']);

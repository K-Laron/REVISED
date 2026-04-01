<?php

declare(strict_types=1);

$router->get('/api/breeds', \App\Controllers\BreedController::class . '@list', ['cors', 'auth']);
$router->get('/api/animals', \App\Controllers\AnimalController::class . '@list', ['cors', 'auth', 'perm:animals.read']);
$router->post('/api/animals', \App\Controllers\AnimalController::class . '@store', ['cors', 'csrf', 'auth', 'perm:animals.create']);
$router->get('/api/animals/{id}/timeline', \App\Controllers\AnimalController::class . '@timeline', ['cors', 'auth', 'perm:animals.read']);
$router->put('/api/animals/{id}/status', \App\Controllers\AnimalController::class . '@updateStatus', ['cors', 'csrf', 'auth', 'perm:animals.update']);
$router->post('/api/animals/{id}/photos', \App\Controllers\AnimalController::class . '@uploadPhoto', ['cors', 'csrf', 'auth', 'perm:animals.update']);
$router->delete('/api/animals/{id}/photos/{photoId}', \App\Controllers\AnimalController::class . '@deletePhoto', ['cors', 'csrf', 'auth', 'perm:animals.update']);
$router->get('/api/animals/{id}/qr', \App\Controllers\QrCodeController::class . '@generate', ['cors', 'auth', 'perm:animals.read']);
$router->get('/api/animals/{id}/qr/download', \App\Controllers\QrCodeController::class . '@download', ['cors', 'auth', 'perm:animals.read']);
$router->get('/api/animals/scan/{qrData}', \App\Controllers\QrCodeController::class . '@scan', ['cors', 'auth']);
$router->get('/api/animals/{id}', \App\Controllers\AnimalController::class . '@get', ['cors', 'auth', 'perm:animals.read']);
$router->put('/api/animals/{id}', \App\Controllers\AnimalController::class . '@update', ['cors', 'csrf', 'auth', 'perm:animals.update']);
$router->delete('/api/animals/{id}', \App\Controllers\AnimalController::class . '@destroy', ['cors', 'csrf', 'auth', 'perm:animals.delete']);
$router->post('/api/animals/{id}/restore', \App\Controllers\AnimalController::class . '@restore', ['cors', 'csrf', 'auth', 'perm:animals.delete']);

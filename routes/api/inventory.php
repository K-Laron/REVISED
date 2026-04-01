<?php

declare(strict_types=1);

$router->get('/api/inventory', \App\Controllers\InventoryController::class . '@list', ['cors', 'auth', 'perm:inventory.read']);
$router->post('/api/inventory', \App\Controllers\InventoryController::class . '@store', ['cors', 'csrf', 'auth', 'perm:inventory.create']);
$router->get('/api/inventory/categories', \App\Controllers\InventoryController::class . '@categories', ['cors', 'auth', 'perm:inventory.read']);
$router->get('/api/inventory/alerts', \App\Controllers\InventoryController::class . '@alerts', ['cors', 'auth', 'perm:inventory.read']);
$router->get('/api/inventory/stats', \App\Controllers\InventoryController::class . '@stats', ['cors', 'auth', 'perm:inventory.read']);
$router->get('/api/inventory/{id}', \App\Controllers\InventoryController::class . '@get', ['cors', 'auth', 'perm:inventory.read']);
$router->put('/api/inventory/{id}', \App\Controllers\InventoryController::class . '@update', ['cors', 'csrf', 'auth', 'perm:inventory.update']);
$router->delete('/api/inventory/{id}', \App\Controllers\InventoryController::class . '@destroy', ['cors', 'csrf', 'auth', 'perm:inventory.delete']);
$router->post('/api/inventory/{id}/stock-in', \App\Controllers\InventoryController::class . '@stockIn', ['cors', 'csrf', 'auth', 'perm:inventory.update']);
$router->post('/api/inventory/{id}/stock-out', \App\Controllers\InventoryController::class . '@stockOut', ['cors', 'csrf', 'auth', 'perm:inventory.update']);
$router->post('/api/inventory/{id}/adjust', \App\Controllers\InventoryController::class . '@adjust', ['cors', 'csrf', 'auth', 'perm:inventory.update']);
$router->get('/api/inventory/{id}/transactions', \App\Controllers\InventoryController::class . '@transactions', ['cors', 'auth', 'perm:inventory.read']);
$router->post('/api/inventory/categories', \App\Controllers\InventoryController::class . '@storeCategory', ['cors', 'csrf', 'auth', 'perm:inventory.create']);

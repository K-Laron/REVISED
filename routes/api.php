<?php

declare(strict_types=1);

$apiRouteFiles = [
    'core',
    'system',
    'auth',
    'dashboard',
    'animals',
    'billing',
    'inventory',
    'kennels',
    'medical',
    'adoptions',
    'users',
    'roles',
    'reports',
    'notifications',
    'search',
];

foreach ($apiRouteFiles as $apiRouteFile) {
    require __DIR__ . '/api/' . $apiRouteFile . '.php';
}

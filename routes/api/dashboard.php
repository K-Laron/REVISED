<?php

declare(strict_types=1);

$router->get('/api/dashboard/stats', \App\Controllers\DashboardController::class . '@stats', ['cors', 'auth']);
$router->get('/api/dashboard/charts/intake', \App\Controllers\DashboardController::class . '@intakeChart', ['cors', 'auth']);
$router->get('/api/dashboard/charts/adoptions', \App\Controllers\DashboardController::class . '@adoptionChart', ['cors', 'auth']);
$router->get('/api/dashboard/charts/occupancy', \App\Controllers\DashboardController::class . '@occupancyChart', ['cors', 'auth']);
$router->get('/api/dashboard/charts/medical', \App\Controllers\DashboardController::class . '@medicalChart', ['cors', 'auth']);
$router->get('/api/dashboard/activity', \App\Controllers\DashboardController::class . '@recentActivity', ['cors', 'auth']);

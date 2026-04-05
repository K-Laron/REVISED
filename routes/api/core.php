<?php

declare(strict_types=1);

use App\Controllers\SystemController;

$router->get('/api/ping', SystemController::class . '@ping');

if (filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOL)) {
    $router->post('/api/validate-test', SystemController::class . '@validateTest');
}

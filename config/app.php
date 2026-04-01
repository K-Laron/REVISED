<?php

declare(strict_types=1);

use App\Core\Logger;
use App\Core\ExceptionHandler;
use App\Core\Session;
use App\Support\SystemSettings;

$systemSettings = SystemSettings::bootstrap();
$appConfig = [
    'name' => $systemSettings['app_name'] ?? ($_ENV['APP_NAME'] ?? 'Catarman Animal Shelter'),
    'env' => $_ENV['APP_ENV'] ?? 'production',
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOL),
    'url' => $_ENV['APP_URL'] ?? 'http://localhost:8000',
    'timezone' => $_ENV['APP_TIMEZONE'] ?? 'Asia/Manila',
    'settings' => $systemSettings,
    'middleware_aliases' => [
        'auth' => App\Middleware\AuthMiddleware::class,
        'guest' => App\Middleware\GuestMiddleware::class,
        'role' => App\Middleware\RoleMiddleware::class,
        'perm' => App\Middleware\PermissionMiddleware::class,
        'throttle' => App\Middleware\RateLimitMiddleware::class,
        'cors' => App\Middleware\CorsMiddleware::class,
        'csrf' => App\Middleware\CsrfMiddleware::class,
    ],
];

date_default_timezone_set($appConfig['timezone']);

if ($appConfig['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', '0');
}

$GLOBALS['app'] = $appConfig;

Session::start();
Logger::instance();
ExceptionHandler::bootTimestamp();
ExceptionHandler::register($appConfig);

return $appConfig;

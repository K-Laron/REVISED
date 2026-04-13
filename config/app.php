<?php

declare(strict_types=1);

use App\Core\Logger;
use App\Core\ExceptionHandler;
use App\Core\Session;
use App\Support\SystemSettings;

$systemSettings = SystemSettings::instance()->bootstrap();
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

// 1. Initialize Registry & Container
$container = App\Core\App::container();

// 2. Register Singletons
$container->singleton(App\Core\Logger::class, function() {
    return new App\Core\Logger();
});
$container->singleton(App\Core\Database::class, function() {
    return new App\Core\Database();
});
$container->singleton(App\Core\Session::class, function() {
    return new App\Core\Session();
});
$container->singleton(Intervention\Image\ImageManager::class, function() {
    if (!extension_loaded('gd')) {
        return null;
    }

    return new Intervention\Image\ImageManager(new Intervention\Image\Drivers\Gd\Driver());
});
$container->singleton(App\Services\Search\SearchModuleCatalog::class, function(App\Core\Container $c) {
    return new App\Services\Search\SearchModuleCatalog([
        $c->get(App\Services\Search\Providers\AnimalsSearchProvider::class),
        $c->get(App\Services\Search\Providers\AdoptionsSearchProvider::class),
        $c->get(App\Services\Search\Providers\MedicalSearchProvider::class),
        $c->get(App\Services\Search\Providers\BillingSearchProvider::class),
        $c->get(App\Services\Search\Providers\InventorySearchProvider::class),
        $c->get(App\Services\Search\Providers\UsersSearchProvider::class),
    ]);
});
$container->singleton(App\Services\SearchService::class, function(App\Core\Container $c) {
    return new App\Services\SearchService(
        [
            $c->get(App\Services\Search\Providers\AnimalsSearchProvider::class),
            $c->get(App\Services\Search\Providers\AdoptionsSearchProvider::class),
            $c->get(App\Services\Search\Providers\MedicalSearchProvider::class),
            $c->get(App\Services\Search\Providers\BillingSearchProvider::class),
            $c->get(App\Services\Search\Providers\InventorySearchProvider::class),
            $c->get(App\Services\Search\Providers\UsersSearchProvider::class),
        ],
        $c->get(App\Services\Search\SearchFilterCatalog::class)
    );
});

// 3. Configure PHP Environment
date_default_timezone_set($appConfig['timezone']);

if ($appConfig['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', '0');
}

$GLOBALS['app'] = $appConfig;

// 4. Start Services
$container->get(App\Core\Session::class)->start();
$container->get(App\Core\Logger::class); // Trigger initialization
App\Core\ExceptionHandler::bootTimestamp();
App\Core\ExceptionHandler::register($appConfig);

return $appConfig;

<?php

declare(strict_types=1);

$projectRoot = dirname(__DIR__);
$loader = require $projectRoot . '/vendor/autoload.php';

spl_autoload_register(static function (string $class) use ($projectRoot): void {
    $prefixes = [
        'App\\' => $projectRoot . '/src/',
        'Tests\\' => $projectRoot . '/tests/',
    ];

    foreach ($prefixes as $prefix => $basePath) {
        if (!str_starts_with($class, $prefix)) {
            continue;
        }

        $relativeClass = substr($class, strlen($prefix));
        $path = $basePath . str_replace('\\', '/', $relativeClass) . '.php';

        if (is_file($path)) {
            require_once $path;
        }

        return;
    }
}, prepend: true);

return $loader;

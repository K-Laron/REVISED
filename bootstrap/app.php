<?php

declare(strict_types=1);

require_once __DIR__ . '/autoload.php';

$projectRoot = dirname(__DIR__);

if (file_exists($projectRoot . '/.env')) {
    \Dotenv\Dotenv::createImmutable($projectRoot)->safeLoad();
}

return require $projectRoot . '/config/app.php';

<?php

declare(strict_types=1);

date_default_timezone_set('Asia/Manila');

require dirname(__DIR__) . '/bootstrap/autoload.php';

if (file_exists(dirname(__DIR__) . '/.env')) {
    \Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
}

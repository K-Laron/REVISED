<?php

declare(strict_types=1);

namespace App\Core;

use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger as MonologLogger;

class Logger
{
    private static ?MonologLogger $instance = null;

    public static function instance(): MonologLogger
    {
        if (self::$instance instanceof MonologLogger) {
            return self::$instance;
        }

        $logDirectory = dirname(__DIR__, 2) . '/' . ($_ENV['LOG_PATH'] ?? 'storage/logs');
        if (!is_dir($logDirectory)) {
            mkdir($logDirectory, 0775, true);
        }

        $logFile = $logDirectory . '/app-' . date('Y-m-d') . '.log';
        $level = Level::fromName(strtoupper($_ENV['LOG_LEVEL'] ?? 'DEBUG'));

        $handler = new StreamHandler($logFile, $level);
        $handler->setFormatter(new JsonFormatter());

        self::$instance = new MonologLogger('app');
        self::$instance->pushHandler($handler);

        return self::$instance;
    }

    public static function debug(string $message, array $context = []): void
    {
        self::instance()->debug($message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::instance()->info($message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::instance()->warning($message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::instance()->error($message, $context);
    }
}

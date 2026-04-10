<?php

declare(strict_types=1);

namespace App\Core;

use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger as MonologLogger;

class Logger
{
    private MonologLogger $logger;

    public function __construct(?string $name = 'app')
    {
        $logDirectory = dirname(__DIR__, 2) . '/' . ($_ENV['LOG_PATH'] ?? 'storage/logs');
        if (!is_dir($logDirectory)) {
            mkdir($logDirectory, 0775, true);
        }

        $logFile = $logDirectory . '/app-' . date('Y-m-d') . '.log';
        $level = Level::fromName(strtoupper($_ENV['LOG_LEVEL'] ?? 'DEBUG'));

        $handler = new StreamHandler($logFile, $level);
        $handler->setFormatter(new JsonFormatter());

        $this->logger = new MonologLogger($name ?? 'app');
        $this->logger->pushHandler($handler);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->logger->debug($message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->logger->info($message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->logger->warning($message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->logger->error($message, $context);
    }

    /*
    |--------------------------------------------------------------------------
    | Static Bridge (Backward Compatibility)
    |--------------------------------------------------------------------------
    */

    private static function getInstance(): self
    {
        return App::container()->get(self::class);
    }

    public static function __callStatic($name, $arguments)
    {
        return self::getInstance()->$name(...$arguments);
    }
}

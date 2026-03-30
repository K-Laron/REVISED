<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOStatement;

class Database
{
    private static ?PDO $connection = null;
    private static int $transactionDepth = 0;

    public static function connect(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $config = require dirname(__DIR__, 2) . '/config/database.php';

        $dsn = sprintf(
            '%s:host=%s;port=%d;dbname=%s;charset=%s',
            $config['driver'],
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );

        self::$connection = new PDO(
            $dsn,
            $config['username'],
            $config['password'],
            $config['options']
        );
        self::$transactionDepth = 0;

        return self::$connection;
    }

    public static function query(string $sql, array $bindings = []): PDOStatement
    {
        $statement = self::connect()->prepare($sql);
        $statement->execute($bindings);

        return $statement;
    }

    public static function fetch(string $sql, array $bindings = []): array|false
    {
        return self::query($sql, $bindings)->fetch();
    }

    public static function fetchAll(string $sql, array $bindings = []): array
    {
        return self::query($sql, $bindings)->fetchAll();
    }

    public static function execute(string $sql, array $bindings = []): bool
    {
        return self::query($sql, $bindings)->rowCount() >= 0;
    }

    public static function beginTransaction(): bool
    {
        $connection = self::connect();

        if (self::$transactionDepth === 0) {
            $started = $connection->beginTransaction();
            if ($started) {
                self::$transactionDepth = 1;
            }

            return $started;
        }

        $connection->exec('SAVEPOINT ' . self::savepointName(self::$transactionDepth));
        self::$transactionDepth++;

        return true;
    }

    public static function commit(): bool
    {
        if (self::$transactionDepth === 0) {
            return false;
        }

        $connection = self::connect();

        if (self::$transactionDepth === 1) {
            $committed = $connection->commit();
            if ($committed) {
                self::$transactionDepth = 0;
            }

            return $committed;
        }

        self::$transactionDepth--;
        $connection->exec('RELEASE SAVEPOINT ' . self::savepointName(self::$transactionDepth));

        return true;
    }

    public static function rollBack(): bool
    {
        if (self::$transactionDepth === 0) {
            return false;
        }

        $connection = self::connect();

        if (self::$transactionDepth === 1) {
            $rolledBack = $connection->rollBack();
            if ($rolledBack) {
                self::$transactionDepth = 0;
            }

            return $rolledBack;
        }

        self::$transactionDepth--;
        $connection->exec('ROLLBACK TO SAVEPOINT ' . self::savepointName(self::$transactionDepth));

        return true;
    }

    public static function lastInsertId(): string|false
    {
        return self::connect()->lastInsertId();
    }

    private static function savepointName(int $depth): string
    {
        return 'app_savepoint_' . $depth;
    }
}

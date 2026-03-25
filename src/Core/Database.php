<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOStatement;

class Database
{
    private static ?PDO $connection = null;

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
        return self::connect()->beginTransaction();
    }

    public static function commit(): bool
    {
        return self::connect()->commit();
    }

    public static function rollBack(): bool
    {
        if (!self::connect()->inTransaction()) {
            return false;
        }

        return self::connect()->rollBack();
    }

    public static function lastInsertId(): string|false
    {
        return self::connect()->lastInsertId();
    }
}

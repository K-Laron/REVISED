<?php

declare(strict_types=1);

namespace App\Core;

use App\Support\Performance\PerformanceProbe;
use PDO;
use PDOStatement;

class Database
{
    private ?PDO $connection = null;
    private int $transactionDepth = 0;
    private array $config;

    /**
     * Database constructor.
     *
     * @param array|null $config Optional config. If null, loads from config/database.php.
     */
    public function __construct(?array $config = null)
    {
        $this->config = $config ?? require dirname(__DIR__, 2) . '/config/database.php';
        $this->transactionDepth = 0;
    }

    /**
     * Get the database configuration.
     *
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Get the active PDO connection instance.
     *
     * @return PDO
     */
    public function instanceConnect(): PDO
    {
        if ($this->connection instanceof PDO) {
            return $this->connection;
        }

        $dsn = sprintf(
            '%s:host=%s;port=%d;dbname=%s;charset=%s',
            $this->config['driver'],
            $this->config['host'],
            $this->config['port'],
            $this->config['database'],
            $this->config['charset']
        );

        $this->connection = new PDO(
            $dsn,
            $this->config['username'],
            $this->config['password'],
            $this->config['options']
        );

        return $this->connection;
    }

    /**
     * Prepare and execute an SQL statement.
     *
     * @param string $sql
     * @param array $bindings
     * @return PDOStatement
     */
    public function instanceQuery(string $sql, array $bindings = []): PDOStatement
    {
        $startedAt = microtime(true);
        $statement = $this->instanceConnect()->prepare($sql);
        $statement->execute($bindings);
        PerformanceProbe::recordQuery($sql, (microtime(true) - $startedAt) * 1000);

        return $statement;
    }

    /**
     * Fetch a single row.
     *
     * @param string $sql
     * @param array $bindings
     * @return array|false
     */
    public function instanceFetch(string $sql, array $bindings = []): array|false
    {
        return $this->instanceQuery($sql, $bindings)->fetch();
    }

    /**
     * Fetch all rows.
     *
     * @param string $sql
     * @param array $bindings
     * @return array
     */
    public function instanceFetchAll(string $sql, array $bindings = []): array
    {
        return $this->instanceQuery($sql, $bindings)->fetchAll();
    }

    /**
     * Execute an SQL statement (insert, update, delete).
     *
     * @param string $sql
     * @param array $bindings
     * @return bool
     */
    public function instanceExecute(string $sql, array $bindings = []): bool
    {
        return $this->instanceQuery($sql, $bindings)->rowCount() >= 0;
    }

    /**
     * Begin a transaction or create a savepoint.
     *
     * @return bool
     */
    public function instanceBeginTransaction(): bool
    {
        $connection = $this->instanceConnect();

        if ($this->transactionDepth === 0) {
            $started = $connection->beginTransaction();
            if ($started) {
                $this->transactionDepth = 1;
            }

            return $started;
        }

        $connection->exec('SAVEPOINT ' . $this->savepointName($this->transactionDepth));
        $this->transactionDepth++;

        return true;
    }

    /**
     * Commit a transaction or release a savepoint.
     *
     * @return bool
     */
    public function instanceCommit(): bool
    {
        if ($this->transactionDepth === 0) {
            return false;
        }

        $connection = $this->instanceConnect();

        if ($this->transactionDepth === 1) {
            $committed = $connection->commit();
            if ($committed) {
                $this->transactionDepth = 0;
            }

            return $committed;
        }

        $this->transactionDepth--;
        $connection->exec('RELEASE SAVEPOINT ' . $this->savepointName($this->transactionDepth));

        return true;
    }

    /**
     * Rollback a transaction or to a savepoint.
     *
     * @return bool
     */
    public function instanceRollBack(): bool
    {
        if ($this->transactionDepth === 0) {
            return false;
        }

        $connection = $this->instanceConnect();

        if ($this->transactionDepth === 1) {
            $rolledBack = $connection->rollBack();
            if ($rolledBack) {
                $this->transactionDepth = 0;
            }

            return $rolledBack;
        }

        $this->transactionDepth--;
        $connection->exec('ROLLBACK TO SAVEPOINT ' . $this->savepointName($this->transactionDepth));

        return true;
    }

    /**
     * Get the last inserted ID.
     *
     * @return string|false
     */
    public function instanceLastInsertId(): string|false
    {
        return $this->instanceConnect()->lastInsertId();
    }

    /**
     * Generate a savepoint name.
     *
     * @param int $depth
     * @return string
     */
    private function savepointName(int $depth): string
    {
        return 'app_savepoint_' . $depth;
    }

    /*
    |--------------------------------------------------------------------------
    | Static Bridge
    |--------------------------------------------------------------------------
    */

    private static function getInstance(): self
    {
        return App::container()->get(self::class);
    }

    public static function query(string $sql, array $bindings = []): PDOStatement
    {
        return self::getInstance()->instanceQuery($sql, $bindings);
    }

    public static function fetch(string $sql, array $bindings = []): array|false
    {
        return self::getInstance()->instanceFetch($sql, $bindings);
    }

    public static function fetchAll(string $sql, array $bindings = []): array
    {
        return self::getInstance()->instanceFetchAll($sql, $bindings);
    }

    public static function execute(string $sql, array $bindings = []): bool
    {
        return self::getInstance()->instanceExecute($sql, $bindings);
    }

    public static function beginTransaction(): bool
    {
        return self::getInstance()->instanceBeginTransaction();
    }

    public static function commit(): bool
    {
        return self::getInstance()->instanceCommit();
    }

    public static function rollBack(): bool
    {
        return self::getInstance()->instanceRollBack();
    }

    public static function lastInsertId(): string|false
    {
        return self::getInstance()->instanceLastInsertId();
    }

    public static function connect(): PDO
    {
        return self::getInstance()->instanceConnect();
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\App;
use App\Core\Database;
use App\Support\Pagination\PaginatedWindow;
use Exception;
use PDOStatement;

abstract class BaseModel
{
    protected static string $table;
    protected static string $primaryKey = 'id';
    protected static bool $useSoftDeletes = true;

    protected Database $db;

    public function __construct()
    {
        $this->db = App::make(Database::class);
    }

    /**
     * Find a record by its primary key.
     *
     * @param int|string $id
     * @param bool $includeDeleted
     * @return array|false
     */
    public function find(int|string $id, bool $includeDeleted = false): array|false
    {
        $sql = "SELECT * FROM " . static::$table . " WHERE " . static::$primaryKey . " = :id";

        if (static::$useSoftDeletes && !$includeDeleted) {
            $sql .= " AND is_deleted = 0";
        }

        return $this->db->fetch($sql, ['id' => $id]);
    }

    /**
     * Create a new record.
     *
     * @param array $data
     * @return int
     */
    public function create(array $data): int
    {
        $columns = array_keys($data);
        $placeholders = array_map(fn ($col) => ":$col", $columns);

        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            static::$table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $this->db->execute($sql, $data);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Update an existing record.
     *
     * @param int|string $id
     * @param array $data
     * @return bool
     */
    public function update(int|string $id, array $data): bool
    {
        $sets = [];
        foreach (array_keys($data) as $column) {
            $sets[] = "$column = :$column";
        }

        $sql = sprintf(
            "UPDATE %s SET %s WHERE %s = :_id_internal",
            static::$table,
            implode(', ', $sets),
            static::$primaryKey
        );

        $bindings = array_merge($data, ['_id_internal' => $id]);

        return $this->db->execute($sql, $bindings);
    }

    /**
     * Delete a record (Soft delete if supported).
     *
     * @param int|string $id
     * @param bool $force
     * @return bool
     */
    public function delete(int|string $id, bool $force = false): bool
    {
        if (static::$useSoftDeletes && !$force) {
            return $this->update($id, [
                'is_deleted' => 1,
                'deleted_at' => date('Y-m-d H:i:s'),
                'deleted_by' => App::container()->has('auth.user_id') ? App::container()->get('auth.user_id') : null
            ]);
        }

        $sql = sprintf(
            "DELETE FROM %s WHERE %s = :id",
            static::$table,
            static::$primaryKey
        );

        return $this->db->execute($sql, ['id' => $id]);
    }

    /**
     * Get all records.
     *
     * @param bool $includeDeleted
     * @return array
     */
    public function all(bool $includeDeleted = false): array
    {
        $sql = "SELECT * FROM " . static::$table;

        if (static::$useSoftDeletes && !$includeDeleted) {
            $sql .= " WHERE is_deleted = 0";
        }

        return $this->db->fetchAll($sql);
    }

    /**
     * Generic pagination helper.
     *
     * @param string $whereSql
     * @param array $bindings
     * @param int $page
     * @param int $perPage
     * @param string $orderBy
     * @return array
     */
    protected function paginateGeneric(string $whereSql, array $bindings, int $page, int $perPage, string $orderBy = 'created_at DESC'): array
    {
        $offset = ($page - 1) * $perPage;

        $sql = sprintf(
            "SELECT * FROM %s %s ORDER BY %s LIMIT %d OFFSET %d",
            static::$table,
            $whereSql,
            $orderBy,
            $perPage + 1,
            $offset
        );

        $rows = $this->db->fetchAll($sql, $bindings);

        return PaginatedWindow::resolve(
            $rows,
            $page,
            $perPage,
            fn (): int => $this->countGeneric($whereSql, $bindings)
        );
    }

    /**
     * Generic count helper for pagination.
     *
     * @param string $whereSql
     * @param array $bindings
     * @return int
     */
    protected function countGeneric(string $whereSql, array $bindings): int
    {
        $sql = sprintf("SELECT COUNT(*) AS aggregate FROM %s %s", static::$table, $whereSql);
        $row = $this->db->fetch($sql, $bindings);

        return (int) ($row['aggregate'] ?? 0);
    }

    public function getGroupedReportData(
        string $dateColumn,
        array $filters,
        string $aggregateSql,
        array $extraAggregates = [],
        string $extraWhere = '1 = 1'
    ): array {
        $groupBy = $filters['group_by'] ?? 'month';
        $periodSql = match ($groupBy) {
            'day' => 'DATE_FORMAT(' . $dateColumn . ', "%Y-%m-%d")',
            'week' => 'DATE_FORMAT(DATE_SUB(' . $dateColumn . ', INTERVAL WEEKDAY(' . $dateColumn . ') DAY), "%Y-%m-%d")',
            'month' => 'DATE_FORMAT(' . $dateColumn . ', "%Y-%m")',
            'quarter' => 'CONCAT(YEAR(' . $dateColumn . '), "-Q", QUARTER(' . $dateColumn . '))',
            'year' => 'DATE_FORMAT(' . $dateColumn . ', "%Y")',
            default => 'DATE_FORMAT(' . $dateColumn . ', "%Y-%m")',
        };

        $selects = implode(",\n                    ", array_merge([$aggregateSql], $extraAggregates));

        return $this->db->fetchAll(
            'SELECT ' . $periodSql . ' AS period,
                    ' . $selects . '
             FROM ' . static::$table . '
             WHERE ' . $extraWhere . '
               AND ' . $dateColumn . ' BETWEEN :start AND :end
             GROUP BY period
             ORDER BY MIN(' . $dateColumn . ') ASC',
            [
                'start' => $filters['start_date'] . ' 00:00:00',
                'end' => $filters['end_date'] . ' 23:59:59',
            ]
        );
    }

    public function getReportSummary(string $sql, array $filters): array
    {
        return $this->db->fetch($sql, [
            'start' => $filters['start_date'] . ' 00:00:00',
            'end' => $filters['end_date'] . ' 23:59:59',
        ]) ?: [];
    }
}

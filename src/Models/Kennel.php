<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Kennel
{
    public function list(array $filters = []): array
    {
        [$whereSql, $bindings] = $this->buildFilters($filters);

        return Database::fetchAll(
            "SELECT *
             FROM `kennels`
             {$whereSql}
             ORDER BY `zone` ASC, `kennel_code` ASC",
            $bindings
        );
    }

    public function find(int $id, bool $includeDeleted = false): array|false
    {
        $sql = 'SELECT * FROM `kennels` WHERE `id` = :id';

        if (!$includeDeleted) {
            $sql .= ' AND `is_deleted` = 0';
        }

        $sql .= ' LIMIT 1';

        return Database::fetch($sql, ['id' => $id]);
    }

    public function create(array $data): int
    {
        Database::execute(
            'INSERT INTO `kennels` (
                `kennel_code`, `zone`, `row_number`, `size_category`, `type`, `allowed_species`, `max_occupants`, `status`, `notes`, `created_by`, `updated_by`
             ) VALUES (
                :kennel_code, :zone, :row_number, :size_category, :type, :allowed_species, :max_occupants, :status, :notes, :created_by, :updated_by
             )',
            $data
        );

        return (int) Database::lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $data['id'] = $id;

        Database::execute(
            'UPDATE `kennels` SET
                `kennel_code` = :kennel_code,
                `zone` = :zone,
                `row_number` = :row_number,
                `size_category` = :size_category,
                `type` = :type,
                `allowed_species` = :allowed_species,
                `max_occupants` = :max_occupants,
                `status` = :status,
                `notes` = :notes,
                `updated_by` = :updated_by
             WHERE `id` = :id',
            $data
        );
    }

    public function setDeleted(int $id, bool $deleted): void
    {
        Database::execute(
            'UPDATE `kennels`
             SET `is_deleted` = :is_deleted,
                 `deleted_at` = :deleted_at
             WHERE `id` = :id',
            [
                'id' => $id,
                'is_deleted' => $deleted ? 1 : 0,
                'deleted_at' => $deleted ? date('Y-m-d H:i:s') : null,
            ]
        );
    }

    public function setStatus(int $id, string $status, ?int $updatedBy): void
    {
        Database::execute(
            'UPDATE `kennels`
             SET `status` = :status,
                 `updated_by` = :updated_by
             WHERE `id` = :id',
            [
                'id' => $id,
                'status' => $status,
                'updated_by' => $updatedBy,
            ]
        );
    }

    public function codeExists(string $code, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT `id` FROM `kennels` WHERE `kennel_code` = :kennel_code';
        $bindings = ['kennel_code' => $code];

        if ($ignoreId !== null) {
            $sql .= ' AND `id` <> :id';
            $bindings['id'] = $ignoreId;
        }

        $sql .= ' LIMIT 1';

        return Database::fetch($sql, $bindings) !== false;
    }

    private function buildFilters(array $filters): array
    {
        $clauses = ['`is_deleted` = 0'];
        $bindings = [];

        foreach (['zone', 'status', 'allowed_species', 'size_category', 'type'] as $field) {
            if (($filters[$field] ?? '') !== '') {
                $clauses[] = "`{$field}` = :{$field}";
                $bindings[$field] = $filters[$field];
            }
        }

        return ['WHERE ' . implode(' AND ', $clauses), $bindings];
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class FeeSchedule
{
    public function list(bool $activeOnly = false): array
    {
        $sql = 'SELECT * FROM fee_schedule';

        if ($activeOnly) {
            $sql .= ' WHERE is_active = 1 AND (effective_to IS NULL OR effective_to >= CURDATE())';
        }

        $sql .= ' ORDER BY category ASC, name ASC';

        return Database::fetchAll($sql);
    }

    public function find(int $id): array|false
    {
        return Database::fetch(
            'SELECT * FROM fee_schedule WHERE id = :id LIMIT 1',
            ['id' => $id]
        );
    }

    public function create(array $data): int
    {
        Database::execute(
            'INSERT INTO fee_schedule (
                category, name, description, amount, is_per_day, species_filter, effective_from, effective_to, is_active, created_by
             ) VALUES (
                :category, :name, :description, :amount, :is_per_day, :species_filter, :effective_from, :effective_to, :is_active, :created_by
             )',
            $data
        );

        return (int) Database::lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $data['id'] = $id;

        Database::execute(
            'UPDATE fee_schedule SET
                category = :category,
                name = :name,
                description = :description,
                amount = :amount,
                is_per_day = :is_per_day,
                species_filter = :species_filter,
                effective_from = :effective_from,
                effective_to = :effective_to,
                is_active = :is_active
             WHERE id = :id',
            $data
        );
    }
}

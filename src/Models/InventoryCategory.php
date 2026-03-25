<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class InventoryCategory
{
    public function list(): array
    {
        return Database::fetchAll('SELECT * FROM inventory_categories ORDER BY name ASC');
    }

    public function create(string $name, ?string $description): int
    {
        Database::execute(
            'INSERT INTO inventory_categories (name, description)
             VALUES (:name, :description)',
            [
                'name' => $name,
                'description' => $description,
            ]
        );

        return (int) Database::lastInsertId();
    }

    public function existsByName(string $name): bool
    {
        return Database::fetch(
            'SELECT id FROM inventory_categories WHERE name = :name LIMIT 1',
            ['name' => $name]
        ) !== false;
    }
}

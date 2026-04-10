<?php

declare(strict_types=1);

namespace App\Models;

class InventoryCategory extends BaseModel
{
    protected static string $table = 'inventory_categories';
    protected static bool $useSoftDeletes = false; // Categories are typically small and don't use soft deletes here

    public function list(): array
    {
        return $this->db->fetchAll('SELECT * FROM inventory_categories ORDER BY name ASC');
    }

    public function existsByName(string $name): bool
    {
        return $this->db->fetch(
            'SELECT id FROM inventory_categories WHERE name = :name LIMIT 1',
            ['name' => $name]
        ) !== false;
    }
}

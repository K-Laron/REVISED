<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class InventoryItem
{
    public function paginate(array $filters, int $page, int $perPage): array
    {
        [$whereSql, $bindings] = $this->buildFilters($filters);
        $offset = ($page - 1) * $perPage;

        $rows = Database::fetchAll(
            "SELECT ii.*, ic.name AS category_name
             FROM inventory_items ii
             INNER JOIN inventory_categories ic ON ic.id = ii.category_id
             {$whereSql}
             ORDER BY ii.name ASC
             LIMIT {$perPage} OFFSET {$offset}",
            $bindings
        );

        $count = Database::fetch(
            "SELECT COUNT(*) AS aggregate
             FROM inventory_items ii
             {$whereSql}",
            $bindings
        );

        return [
            'items' => $rows,
            'total' => (int) ($count['aggregate'] ?? 0),
        ];
    }

    public function find(int $id): array|false
    {
        return Database::fetch(
            'SELECT ii.*, ic.name AS category_name
             FROM inventory_items ii
             INNER JOIN inventory_categories ic ON ic.id = ii.category_id
             WHERE ii.id = :id
               AND ii.is_deleted = 0
             LIMIT 1',
            ['id' => $id]
        );
    }

    public function create(array $data): int
    {
        Database::execute(
            'INSERT INTO inventory_items (
                sku, name, category_id, unit_of_measure, cost_per_unit, supplier_name, supplier_contact,
                reorder_level, quantity_on_hand, storage_location, expiry_date, is_active, created_by, updated_by
             ) VALUES (
                :sku, :name, :category_id, :unit_of_measure, :cost_per_unit, :supplier_name, :supplier_contact,
                :reorder_level, :quantity_on_hand, :storage_location, :expiry_date, :is_active, :created_by, :updated_by
             )',
            $data
        );

        return (int) Database::lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $data['id'] = $id;

        Database::execute(
            'UPDATE inventory_items SET
                sku = :sku,
                name = :name,
                category_id = :category_id,
                unit_of_measure = :unit_of_measure,
                cost_per_unit = :cost_per_unit,
                supplier_name = :supplier_name,
                supplier_contact = :supplier_contact,
                reorder_level = :reorder_level,
                storage_location = :storage_location,
                expiry_date = :expiry_date,
                is_active = :is_active,
                updated_by = :updated_by
             WHERE id = :id',
            $data
        );
    }

    public function updateQuantity(int $id, int $quantityOnHand, ?int $updatedBy): void
    {
        Database::execute(
            'UPDATE inventory_items
             SET quantity_on_hand = :quantity_on_hand,
                 updated_by = :updated_by
             WHERE id = :id',
            [
                'id' => $id,
                'quantity_on_hand' => $quantityOnHand,
                'updated_by' => $updatedBy,
            ]
        );
    }

    public function setDeleted(int $id, bool $deleted): void
    {
        Database::execute(
            'UPDATE inventory_items
             SET is_deleted = :is_deleted,
                 deleted_at = :deleted_at
             WHERE id = :id',
            [
                'id' => $id,
                'is_deleted' => $deleted ? 1 : 0,
                'deleted_at' => $deleted ? date('Y-m-d H:i:s') : null,
            ]
        );
    }

    public function skuExists(string $sku, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT id FROM inventory_items WHERE sku = :sku LIMIT 1';
        $bindings = ['sku' => $sku];

        if ($ignoreId !== null) {
            $sql = 'SELECT id FROM inventory_items WHERE sku = :sku AND id <> :id LIMIT 1';
            $bindings['id'] = $ignoreId;
        }

        return Database::fetch($sql, $bindings) !== false;
    }

    private function buildFilters(array $filters): array
    {
        $clauses = ['ii.is_deleted = 0'];
        $bindings = [];

        if (($filters['search'] ?? '') !== '') {
            $clauses[] = '(ii.sku LIKE :search OR ii.name LIKE :search)';
            $bindings['search'] = '%' . $filters['search'] . '%';
        }

        if (($filters['category_id'] ?? '') !== '') {
            $clauses[] = 'ii.category_id = :category_id';
            $bindings['category_id'] = (int) $filters['category_id'];
        }

        if (($filters['status'] ?? '') === 'low_stock') {
            $clauses[] = 'ii.quantity_on_hand <= ii.reorder_level';
        }

        if (($filters['status'] ?? '') === 'expiring') {
            $clauses[] = 'ii.expiry_date IS NOT NULL AND ii.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)';
        }

        return ['WHERE ' . implode(' AND ', $clauses), $bindings];
    }
}

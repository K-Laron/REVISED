<?php

declare(strict_types=1);

namespace App\Models;

class InventoryItem extends BaseModel
{
    protected static string $table = 'inventory_items';

    public function paginate(array $filters, int $page, int $perPage): array
    {
        [$whereSql, $bindings] = $this->buildFilters($filters);
        $offset = ($page - 1) * $perPage;

        $rows = $this->db->fetchAll(
            "SELECT ii.*, ic.name AS category_name
             FROM inventory_items ii
             INNER JOIN inventory_categories ic ON ic.id = ii.category_id
             {$whereSql}
             ORDER BY ii.name ASC
             LIMIT {$perPage} OFFSET {$offset}",
            $bindings
        );

        $count = $this->db->fetch(
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

    public function find(int|string $id, bool $includeDeleted = false): array|false
    {
        return $this->db->fetch(
            'SELECT ii.*, ic.name AS category_name
             FROM inventory_items ii
             INNER JOIN inventory_categories ic ON ic.id = ii.category_id
             WHERE ii.id = :id
               AND (ii.is_deleted = 0 OR :include_deleted = 1)
             LIMIT 1',
            ['id' => $id, 'include_deleted' => $includeDeleted ? 1 : 0]
        );
    }

    public function updateQuantity(int $id, int $quantityOnHand, ?int $updatedBy): void
    {
        $this->update($id, [
            'quantity_on_hand' => $quantityOnHand,
            'updated_by' => $updatedBy,
        ]);
    }

    public function skuExists(string $sku, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT id FROM inventory_items WHERE sku = :sku';
        $bindings = ['sku' => $sku];

        if ($ignoreId !== null) {
            $sql .= ' AND id <> :id';
            $bindings['id'] = $ignoreId;
        }

        $sql .= ' LIMIT 1';

        return $this->db->fetch($sql, $bindings) !== false;
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

    public function getAlerts(): array
    {
        $lowStock = $this->db->fetchAll(
            'SELECT ii.*, ic.name AS category_name
             FROM inventory_items ii
             INNER JOIN inventory_categories ic ON ic.id = ii.category_id
             WHERE ii.is_deleted = 0
               AND ii.quantity_on_hand <= ii.reorder_level
             ORDER BY ii.quantity_on_hand ASC, ii.name ASC'
        );

        $expiring = $this->db->fetchAll(
            'SELECT ii.*, ic.name AS category_name
             FROM inventory_items ii
             INNER JOIN inventory_categories ic ON ic.id = ii.category_id
             WHERE ii.is_deleted = 0
               AND ii.expiry_date IS NOT NULL
               AND ii.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
             ORDER BY ii.expiry_date ASC, ii.name ASC'
        );

        return [
            'low_stock' => $lowStock,
            'expiring' => $expiring,
        ];
    }

    public function getAlertSummary(): array
    {
        $row = $this->db->fetch(
            "SELECT
                COALESCE(SUM(CASE WHEN quantity_on_hand <= reorder_level THEN 1 ELSE 0 END), 0) AS low_stock_count,
                COALESCE(SUM(CASE WHEN expiry_date IS NOT NULL AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END), 0) AS expiring_count
             FROM inventory_items
             WHERE is_deleted = 0"
        );

        return [
            'low_stock_count' => (int) ($row['low_stock_count'] ?? 0),
            'expiring_count' => (int) ($row['expiring_count'] ?? 0),
        ];
    }

    public function getStats(): array
    {
        $row = $this->db->fetch(
            "SELECT
                COUNT(*) AS total_items,
                COALESCE(SUM(quantity_on_hand), 0) AS total_units,
                COALESCE(SUM(CASE WHEN quantity_on_hand <= reorder_level THEN 1 ELSE 0 END), 0) AS low_stock_count,
                COALESCE(SUM(CASE WHEN expiry_date IS NOT NULL AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END), 0) AS expiring_count,
                COALESCE(SUM(quantity_on_hand * COALESCE(cost_per_unit, 0)), 0) AS estimated_value
             FROM inventory_items
             WHERE is_deleted = 0"
        );

        return [
            'total_items' => (int) ($row['total_items'] ?? 0),
            'total_units' => (int) ($row['total_units'] ?? 0),
            'low_stock_count' => (int) ($row['low_stock_count'] ?? 0),
            'expiring_count' => (int) ($row['expiring_count'] ?? 0),
            'estimated_value' => (float) ($row['estimated_value'] ?? 0),
        ];
    }

    public function listForProcedures(): array
    {
        return $this->db->fetchAll(
            'SELECT ii.id, ii.sku, ii.name, ii.quantity_on_hand, ii.unit_of_measure, ic.name AS category_name
             FROM inventory_items ii
             INNER JOIN inventory_categories ic ON ic.id = ii.category_id
             WHERE ii.is_deleted = 0
               AND ii.is_active = 1
             ORDER BY ic.name ASC, ii.name ASC'
        );
    }
}

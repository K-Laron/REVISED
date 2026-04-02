<?php

declare(strict_types=1);

namespace App\Services\Search\Providers;

use App\Core\Database;
use App\Services\Search\AbstractSearchProvider;

final class InventorySearchProvider extends AbstractSearchProvider
{
    public function key(): string
    {
        return 'inventory';
    }

    public function label(): string
    {
        return 'Inventory';
    }

    public function permission(): string
    {
        return 'inventory.read';
    }

    public function search(string $term, int $limit, array $filters): array
    {
        $bindings = $this->likeBindings($term, 2);
        $filterClause = $this->inventoryFilterClause((string) ($filters['inventory_status'] ?? ''), $filters);
        $rows = Database::fetchAll(
            "SELECT ii.id, ii.sku, ii.name, ii.quantity_on_hand, ii.reorder_level, ii.expiry_date, ii.is_active, ic.name AS category_name
             FROM inventory_items ii
             INNER JOIN inventory_categories ic ON ic.id = ii.category_id
             WHERE ii.is_deleted = 0
               AND (ii.sku LIKE :search_1 OR ii.name LIKE :search_2)"
               . $filterClause['sql'] . "
             ORDER BY ii.name ASC
             LIMIT " . ($limit + 1),
            $bindings + $filterClause['bindings']
        );
        $preview = $this->previewResult(
            $rows,
            $limit,
            static fn (): int => (int) ((Database::fetch(
                "SELECT COUNT(*) AS aggregate
                 FROM inventory_items ii
                 INNER JOIN inventory_categories ic ON ic.id = ii.category_id
                 WHERE ii.is_deleted = 0
                   AND (ii.sku LIKE :search_1 OR ii.name LIKE :search_2)"
                   . $filterClause['sql'],
                $bindings + $filterClause['bindings']
            )['aggregate'] ?? 0))
        );

        return $this->section(
            'inventory',
            'Inventory',
            '/inventory',
            $preview['count'],
            array_map(static fn (array $item): array => [
                'title' => (string) ($item['name'] ?? ''),
                'subtitle' => trim((string) (($item['sku'] ?? '') . ' • ' . ($item['category_name'] ?? ''))),
                'meta' => 'On hand: ' . (int) ($item['quantity_on_hand'] ?? 0),
                'badge' => self::inventoryBadge($item),
                'href' => '/inventory/' . (int) $item['id'],
            ], $preview['items'])
        );
    }
}

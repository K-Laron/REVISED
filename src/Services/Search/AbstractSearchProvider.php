<?php

declare(strict_types=1);

namespace App\Services\Search;

abstract class AbstractSearchProvider implements SearchProviderInterface
{
    protected function section(string $key, string $label, string $href, int $count, array $items): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'href' => $href,
            'count' => $count,
            'items' => $items,
        ];
    }

    protected function likeBindings(string $term, int $count): array
    {
        $bindings = [];
        $value = '%' . $term . '%';

        for ($index = 1; $index <= $count; $index++) {
            $bindings['search_' . $index] = $value;
        }

        return $bindings;
    }

    protected function previewResult(array $rows, int $limit, callable $countQuery): array
    {
        $overflow = count($rows) > $limit;
        $items = $overflow ? array_slice($rows, 0, $limit) : $rows;

        return [
            'count' => $overflow ? (int) $countQuery() : count($items),
            'items' => $items,
        ];
    }

    protected function standardFilterClause(string $statusValue, array $filters, string $statusColumn, string $dateColumn, string $prefix): array
    {
        $clauses = [];
        $bindings = [];

        if ($statusValue !== '') {
            $clauses[] = $statusColumn . ' = :' . $prefix . '_status';
            $bindings[$prefix . '_status'] = $statusValue;
        }

        if (($filters['date_from'] ?? null) !== null) {
            $clauses[] = 'DATE(' . $dateColumn . ') >= :' . $prefix . '_date_from';
            $bindings[$prefix . '_date_from'] = $filters['date_from'];
        }

        if (($filters['date_to'] ?? null) !== null) {
            $clauses[] = 'DATE(' . $dateColumn . ') <= :' . $prefix . '_date_to';
            $bindings[$prefix . '_date_to'] = $filters['date_to'];
        }

        return [
            'sql' => $clauses === [] ? '' : ' AND ' . implode(' AND ', $clauses),
            'bindings' => $bindings,
        ];
    }

    protected function inventoryFilterClause(string $status, array $filters): array
    {
        $clauses = [];
        $bindings = [];

        if ($status !== '') {
            if ($status === 'low_stock') {
                $clauses[] = 'ii.quantity_on_hand <= ii.reorder_level';
            } elseif ($status === 'expiring') {
                $clauses[] = 'ii.expiry_date IS NOT NULL AND ii.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)';
            } elseif ($status === 'active' || $status === 'inactive') {
                $clauses[] = 'ii.is_active = :inventory_active';
                $bindings['inventory_active'] = $status === 'active' ? 1 : 0;
            } elseif (str_contains($status, 'low')) {
                $clauses[] = 'ii.quantity_on_hand <= ii.reorder_level';
            } elseif (str_contains($status, 'expir')) {
                $clauses[] = 'ii.expiry_date IS NOT NULL AND ii.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)';
            } else {
                $clauses[] = '(LOWER(ic.name) LIKE :inventory_status OR LOWER(ii.name) LIKE :inventory_status)';
                $bindings['inventory_status'] = '%' . $status . '%';
            }
        }

        if (($filters['date_from'] ?? null) !== null) {
            $clauses[] = 'ii.expiry_date IS NOT NULL AND ii.expiry_date >= :inventory_date_from';
            $bindings['inventory_date_from'] = $filters['date_from'];
        }

        if (($filters['date_to'] ?? null) !== null) {
            $clauses[] = 'ii.expiry_date IS NOT NULL AND ii.expiry_date <= :inventory_date_to';
            $bindings['inventory_date_to'] = $filters['date_to'];
        }

        return [
            'sql' => $clauses === [] ? '' : ' AND ' . implode(' AND ', $clauses),
            'bindings' => $bindings,
        ];
    }

    protected function userFilterClause(string $status, array $filters): array
    {
        $clauses = [];
        $bindings = [];

        if ($status !== '') {
            if ($status === 'active' || $status === 'inactive') {
                $clauses[] = 'u.is_active = :users_active';
                $bindings['users_active'] = $status === 'active' ? 1 : 0;
            } else {
                $clauses[] = 'LOWER(r.display_name) LIKE :users_status';
                $bindings['users_status'] = '%' . $status . '%';
            }
        }

        if (($filters['date_from'] ?? null) !== null) {
            $clauses[] = 'DATE(u.created_at) >= :users_date_from';
            $bindings['users_date_from'] = $filters['date_from'];
        }

        if (($filters['date_to'] ?? null) !== null) {
            $clauses[] = 'DATE(u.created_at) <= :users_date_to';
            $bindings['users_date_to'] = $filters['date_to'];
        }

        return [
            'sql' => $clauses === [] ? '' : ' AND ' . implode(' AND ', $clauses),
            'bindings' => $bindings,
        ];
    }

    protected static function inventoryBadge(array $item): string
    {
        if (!empty($item['expiry_date']) && strtotime((string) $item['expiry_date']) <= strtotime('+30 days')) {
            return 'Expiring';
        }

        if ((int) ($item['quantity_on_hand'] ?? 0) <= (int) ($item['reorder_level'] ?? 0)) {
            return 'Low Stock';
        }

        return !empty($item['is_active']) ? 'Active' : 'Inactive';
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Request;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\StockTransaction;
use RuntimeException;

class InventoryService
{
    private InventoryItem $items;
    private InventoryCategory $categories;
    private StockTransaction $transactions;
    private AuditService $audit;

    public function __construct()
    {
        $this->items = new InventoryItem();
        $this->categories = new InventoryCategory();
        $this->transactions = new StockTransaction();
        $this->audit = new AuditService();
    }

    public function list(array $filters, int $page, int $perPage): array
    {
        $result = $this->items->paginate($filters, $page, $perPage);

        foreach ($result['items'] as &$item) {
            $item['is_low_stock'] = (int) $item['quantity_on_hand'] <= (int) $item['reorder_level'];
            $item['is_expiring'] = $item['expiry_date'] !== null && strtotime((string) $item['expiry_date']) <= strtotime('+30 days');
        }
        unset($item);

        return $result;
    }

    public function get(int $id): array
    {
        $item = $this->items->find($id);
        if ($item === false) {
            throw new RuntimeException('Inventory item not found.');
        }

        $item['transactions'] = $this->transactions->listByItem($id);
        $item['is_low_stock'] = (int) $item['quantity_on_hand'] <= (int) $item['reorder_level'];
        $item['is_expiring'] = $item['expiry_date'] !== null && strtotime((string) $item['expiry_date']) <= strtotime('+30 days');

        return $item;
    }

    public function create(array $data, int $userId, Request $request): array
    {
        if ($this->items->skuExists((string) $data['sku'])) {
            throw new RuntimeException('SKU is already in use.');
        }

        $itemId = $this->items->create($this->normalizePayload($data, $userId, true));
        $item = $this->get($itemId);
        $this->audit->record($userId, 'create', 'inventory', 'inventory_items', $itemId, [], $item, $request);

        return $item;
    }

    public function update(int $id, array $data, int $userId, Request $request): array
    {
        $current = $this->get($id);
        if ($this->items->skuExists((string) $data['sku'], $id)) {
            throw new RuntimeException('SKU is already in use.');
        }

        $this->items->update($id, $this->normalizePayload($data, $userId, false));
        $item = $this->get($id);
        $this->audit->record($userId, 'update', 'inventory', 'inventory_items', $id, $current, $item, $request);

        return $item;
    }

    public function delete(int $id, int $userId, Request $request): void
    {
        $item = $this->get($id);
        $this->items->setDeleted($id, true);
        $this->audit->record($userId, 'delete', 'inventory', 'inventory_items', $id, $item, ['is_deleted' => true], $request);
    }

    public function categories(): array
    {
        return $this->categories->list();
    }

    public function storeCategory(string $name, ?string $description, int $userId, Request $request): array
    {
        if ($this->categories->existsByName($name)) {
            throw new RuntimeException('Category name is already in use.');
        }

        $id = $this->categories->create($name, $description);
        $category = Database::fetch('SELECT * FROM inventory_categories WHERE id = :id LIMIT 1', ['id' => $id]) ?: [];
        $this->audit->record($userId, 'create', 'inventory', 'inventory_categories', $id, [], $category, $request);

        return $category;
    }

    public function transactions(int $itemId): array
    {
        $this->get($itemId);

        return $this->transactions->listByItem($itemId);
    }

    public function stockIn(int $itemId, array $data, int $userId, Request $request): array
    {
        return $this->applyStockChange($itemId, $data, $userId, $request, 'stock_in');
    }

    public function stockOut(int $itemId, array $data, int $userId, Request $request): array
    {
        return $this->applyStockChange($itemId, $data, $userId, $request, 'stock_out');
    }

    public function adjust(int $itemId, array $data, int $userId, Request $request): array
    {
        $item = $this->get($itemId);
        $quantityBefore = (int) $item['quantity_on_hand'];
        $quantityAfter = (int) $data['quantity'];
        $difference = $quantityAfter - $quantityBefore;

        Database::beginTransaction();

        try {
            $this->items->updateQuantity($itemId, $quantityAfter, $userId);
            $transactionId = $this->transactions->create([
                'inventory_item_id' => $itemId,
                'transaction_type' => 'adjust',
                'quantity' => $difference,
                'quantity_before' => $quantityBefore,
                'quantity_after' => $quantityAfter,
                'reason' => $data['reason'],
                'reference_type' => null,
                'reference_id' => null,
                'batch_lot_number' => null,
                'expiry_date' => ($data['expiry_date'] ?? '') !== '' ? $data['expiry_date'] : null,
                'source_supplier' => null,
                'notes' => $data['notes'] !== '' ? $data['notes'] : null,
                'transacted_by' => $userId,
            ]);
            Database::commit();
        } catch (\Throwable $exception) {
            Database::rollBack();
            throw $exception;
        }

        $updated = $this->get($itemId);
        $this->audit->record($userId, 'update', 'inventory', 'stock_transactions', $transactionId, ['quantity_on_hand' => $quantityBefore], ['quantity_on_hand' => $quantityAfter], $request);

        $reorderLevel = (int) $item['reorder_level'];
        if ($quantityBefore > $reorderLevel && $quantityAfter <= $reorderLevel) {
            (new \App\Services\NotificationService())->notifyRole('shelter_staff', [
                'type' => 'warning',
                'title' => 'Low Stock Alert',
                'message' => 'Item ' . $item['name'] . ' has dropped to or below its reorder level.',
                'link' => '/inventory/' . $itemId
            ]);
        }

        return $updated;
    }

    public function alerts(): array
    {
        $lowStock = Database::fetchAll(
            'SELECT ii.*, ic.name AS category_name
             FROM inventory_items ii
             INNER JOIN inventory_categories ic ON ic.id = ii.category_id
             WHERE ii.is_deleted = 0
               AND ii.quantity_on_hand <= ii.reorder_level
             ORDER BY ii.quantity_on_hand ASC, ii.name ASC'
        );

        $expiring = Database::fetchAll(
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

    public function stats(): array
    {
        $row = Database::fetch(
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

    private function applyStockChange(int $itemId, array $data, int $userId, Request $request, string $type): array
    {
        $item = $this->get($itemId);
        $quantity = (int) $data['quantity'];
        $quantityBefore = (int) $item['quantity_on_hand'];
        $quantityAfter = $type === 'stock_in'
            ? $quantityBefore + $quantity
            : $quantityBefore - $quantity;

        if ($quantityAfter < 0) {
            throw new RuntimeException('Stock cannot go below zero.');
        }

        Database::beginTransaction();

        try {
            $this->items->updateQuantity($itemId, $quantityAfter, $userId);
            $transactionId = $this->transactions->create([
                'inventory_item_id' => $itemId,
                'transaction_type' => $type,
                'quantity' => $type === 'stock_in' ? $quantity : -$quantity,
                'quantity_before' => $quantityBefore,
                'quantity_after' => $quantityAfter,
                'reason' => $data['reason'],
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => ($data['reference_id'] ?? '') !== '' ? (int) $data['reference_id'] : null,
                'batch_lot_number' => ($data['batch_lot_number'] ?? '') !== '' ? $data['batch_lot_number'] : null,
                'expiry_date' => ($data['expiry_date'] ?? '') !== '' ? $data['expiry_date'] : null,
                'source_supplier' => ($data['source_supplier'] ?? '') !== '' ? $data['source_supplier'] : null,
                'notes' => $data['notes'] !== '' ? $data['notes'] : null,
                'transacted_by' => $userId,
            ]);
            Database::commit();
        } catch (\Throwable $exception) {
            Database::rollBack();
            throw $exception;
        }

        $updated = $this->get($itemId);
        $this->audit->record($userId, 'update', 'inventory', 'stock_transactions', $transactionId, ['quantity_on_hand' => $quantityBefore], ['quantity_on_hand' => $quantityAfter], $request);

        $reorderLevel = (int) $item['reorder_level'];
        if ($quantityBefore > $reorderLevel && $quantityAfter <= $reorderLevel) {
            (new \App\Services\NotificationService())->notifyRole('shelter_staff', [
                'type' => 'warning',
                'title' => 'Low Stock Alert',
                'message' => 'Item ' . $item['name'] . ' has dropped to or below its reorder level.',
                'link' => '/inventory/' . $itemId
            ]);
        }

        return $updated;
    }

    private function normalizePayload(array $data, int $userId, bool $creating): array
    {
        return [
            'sku' => trim((string) $data['sku']),
            'name' => trim((string) $data['name']),
            'category_id' => (int) $data['category_id'],
            'unit_of_measure' => $data['unit_of_measure'],
            'cost_per_unit' => ($data['cost_per_unit'] ?? '') !== '' ? round((float) $data['cost_per_unit'], 2) : null,
            'supplier_name' => ($data['supplier_name'] ?? '') !== '' ? $data['supplier_name'] : null,
            'supplier_contact' => ($data['supplier_contact'] ?? '') !== '' ? $data['supplier_contact'] : null,
            'reorder_level' => (int) $data['reorder_level'],
            'quantity_on_hand' => $creating ? (int) $data['quantity_on_hand'] : 0,
            'storage_location' => ($data['storage_location'] ?? '') !== '' ? $data['storage_location'] : null,
            'expiry_date' => ($data['expiry_date'] ?? '') !== '' ? $data['expiry_date'] : null,
            'is_active' => filter_var($data['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
            'created_by' => $creating ? $userId : null,
            'updated_by' => $userId,
        ];
    }
}

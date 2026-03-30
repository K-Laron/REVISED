<?php

declare(strict_types=1);

namespace App\Services\Medical;

use App\Models\InventoryItem;
use App\Models\StockTransaction;
use RuntimeException;

class TreatmentInventorySynchronizer
{
    public function __construct(
        private readonly InventoryItem $inventoryItems,
        private readonly StockTransaction $stockTransactions
    ) {
    }

    public function sync(?array $existing, array $next, int $userId, int $medicalRecordId): void
    {
        $existingItemId = (($existing['inventory_item_id'] ?? null) !== null) ? (int) $existing['inventory_item_id'] : null;
        $nextItemId = (($next['inventory_item_id'] ?? null) !== null) ? (int) $next['inventory_item_id'] : null;
        $existingQty = (int) ($existing['quantity_dispensed'] ?? 0);
        $nextQty = (int) ($next['quantity_dispensed'] ?? 0);

        if ($existingItemId !== null && $existingQty > 0 && $existingItemId !== $nextItemId) {
            $this->adjust($existingItemId, $existingQty, $userId, $medicalRecordId, 'return', 'Treatment inventory reassigned.');
            $existingQty = 0;
        }

        if ($existingItemId !== null && $nextItemId === $existingItemId) {
            $delta = $nextQty - $existingQty;
            if ($delta !== 0) {
                $this->adjust(
                    $nextItemId,
                    -$delta,
                    $userId,
                    $medicalRecordId,
                    $delta > 0 ? 'dispensed' : 'return',
                    $delta > 0 ? 'Additional medication dispensed.' : 'Medication quantity reduced.'
                );
            }

            return;
        }

        if ($nextItemId !== null && $nextQty > 0) {
            $this->adjust($nextItemId, -$nextQty, $userId, $medicalRecordId, 'dispensed', 'Medication dispensed for treatment record.');
        }
    }

    public function restore(array $details, int $userId, int $medicalRecordId): void
    {
        $inventoryItemId = (($details['inventory_item_id'] ?? null) !== null) ? (int) $details['inventory_item_id'] : null;
        $quantity = (int) ($details['quantity_dispensed'] ?? 0);

        if ($inventoryItemId !== null && $quantity > 0) {
            $this->adjust($inventoryItemId, $quantity, $userId, $medicalRecordId, 'return', 'Treatment record deleted; stock restored.');
        }
    }

    private function adjust(int $itemId, int $delta, int $userId, int $medicalRecordId, string $reason, string $notes): void
    {
        $item = $this->inventoryItems->find($itemId);
        if ($item === false) {
            throw new RuntimeException('Linked inventory item not found.');
        }

        $before = (int) $item['quantity_on_hand'];
        $after = $before + $delta;

        if ($after < 0) {
            throw new RuntimeException('Inventory quantity is insufficient for the requested treatment dispense.');
        }

        $this->inventoryItems->updateQuantity($itemId, $after, $userId);
        $this->stockTransactions->create([
            'inventory_item_id' => $itemId,
            'transaction_type' => $delta >= 0 ? 'stock_in' : 'stock_out',
            'quantity' => $delta,
            'quantity_before' => $before,
            'quantity_after' => $after,
            'reason' => $reason,
            'reference_type' => 'medical_record',
            'reference_id' => $medicalRecordId,
            'batch_lot_number' => null,
            'expiry_date' => null,
            'source_supplier' => null,
            'notes' => $notes,
            'transacted_by' => $userId,
        ]);
    }
}

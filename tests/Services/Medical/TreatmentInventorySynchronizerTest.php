<?php

declare(strict_types=1);

namespace Tests\Services\Medical;

use App\Models\InventoryItem;
use App\Models\StockTransaction;
use App\Services\Medical\TreatmentInventorySynchronizer;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class TreatmentInventorySynchronizerTest extends TestCase
{
    public function testSyncAdjustsInventoryForAdditionalDispensedQuantity(): void
    {
        $inventoryItems = $this->createMock(InventoryItem::class);
        $stockTransactions = $this->createMock(StockTransaction::class);

        $inventoryItems->expects(self::once())
            ->method('find')
            ->with(11)
            ->willReturn([
                'quantity_on_hand' => 10,
            ]);

        $inventoryItems->expects(self::once())
            ->method('updateQuantity')
            ->with(11, 7, 5);

        $stockTransactions->expects(self::once())
            ->method('create')
            ->with(self::callback(function (array $payload): bool {
                return $payload['inventory_item_id'] === 11
                    && $payload['transaction_type'] === 'stock_out'
                    && $payload['quantity'] === -3
                    && $payload['quantity_before'] === 10
                    && $payload['quantity_after'] === 7
                    && $payload['reason'] === 'dispensed'
                    && $payload['reference_id'] === 21;
            }))
            ->willReturn(1);

        $synchronizer = new TreatmentInventorySynchronizer($inventoryItems, $stockTransactions);
        $synchronizer->sync(
            ['inventory_item_id' => 11, 'quantity_dispensed' => 2],
            ['inventory_item_id' => 11, 'quantity_dispensed' => 5],
            5,
            21
        );
    }

    public function testSyncRejectsDispenseWhenInventoryWouldBecomeNegative(): void
    {
        $inventoryItems = $this->createMock(InventoryItem::class);
        $stockTransactions = $this->createMock(StockTransaction::class);

        $inventoryItems->expects(self::once())
            ->method('find')
            ->with(11)
            ->willReturn([
                'quantity_on_hand' => 1,
            ]);

        $inventoryItems->expects(self::never())->method('updateQuantity');
        $stockTransactions->expects(self::never())->method('create');

        $synchronizer = new TreatmentInventorySynchronizer($inventoryItems, $stockTransactions);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Inventory quantity is insufficient for the requested treatment dispense.');

        $synchronizer->sync(
            null,
            ['inventory_item_id' => 11, 'quantity_dispensed' => 5],
            5,
            21
        );
    }
}

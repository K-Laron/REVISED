<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class StockTransaction
{
    public function listByItem(int $itemId): array
    {
        return Database::fetchAll(
            'SELECT * FROM stock_transactions
             WHERE inventory_item_id = :inventory_item_id
             ORDER BY transacted_at DESC, id DESC',
            ['inventory_item_id' => $itemId]
        );
    }

    public function create(array $data): int
    {
        Database::execute(
            'INSERT INTO stock_transactions (
                inventory_item_id, transaction_type, quantity, quantity_before, quantity_after, reason,
                reference_type, reference_id, batch_lot_number, expiry_date, source_supplier, notes, transacted_by
             ) VALUES (
                :inventory_item_id, :transaction_type, :quantity, :quantity_before, :quantity_after, :reason,
                :reference_type, :reference_id, :batch_lot_number, :expiry_date, :source_supplier, :notes, :transacted_by
             )',
            $data
        );

        return (int) Database::lastInsertId();
    }
}

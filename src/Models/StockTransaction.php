<?php

declare(strict_types=1);

namespace App\Models;

class StockTransaction extends BaseModel
{
    protected static string $table = 'stock_transactions';
    protected static bool $useSoftDeletes = false; // Transactions are immutable records

    public function listByItem(int $itemId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM stock_transactions
             WHERE inventory_item_id = :inventory_item_id
             ORDER BY transacted_at DESC, id DESC',
            ['inventory_item_id' => $itemId]
        );
    }
}

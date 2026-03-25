<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class InvoiceLineItem
{
    public function listByInvoice(int $invoiceId): array
    {
        return Database::fetchAll(
            'SELECT ili.*, fs.category AS fee_category, fs.name AS fee_name
             FROM invoice_line_items ili
             LEFT JOIN fee_schedule fs ON fs.id = ili.fee_schedule_id
             WHERE ili.invoice_id = :invoice_id
             ORDER BY ili.sort_order ASC, ili.id ASC',
            ['invoice_id' => $invoiceId]
        );
    }

    public function createMany(int $invoiceId, array $items): void
    {
        foreach ($items as $index => $item) {
            Database::execute(
                'INSERT INTO invoice_line_items (
                    invoice_id, fee_schedule_id, description, quantity, unit_price, sort_order
                 ) VALUES (
                    :invoice_id, :fee_schedule_id, :description, :quantity, :unit_price, :sort_order
                 )',
                [
                    'invoice_id' => $invoiceId,
                    'fee_schedule_id' => $item['fee_schedule_id'],
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'sort_order' => $index,
                ]
            );
        }
    }

    public function deleteByInvoice(int $invoiceId): void
    {
        Database::execute('DELETE FROM invoice_line_items WHERE invoice_id = :invoice_id', ['invoice_id' => $invoiceId]);
    }
}

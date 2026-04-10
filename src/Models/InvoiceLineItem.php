<?php

declare(strict_types=1);

namespace App\Models;

class InvoiceLineItem extends BaseModel
{
    protected static string $table = 'invoice_line_items';
    protected static bool $useSoftDeletes = false; // Linked to invoice deletion

    public function listByInvoice(int $invoiceId): array
    {
        return $this->db->fetchAll(
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
            $this->create([
                'invoice_id' => $invoiceId,
                'fee_schedule_id' => $item['fee_schedule_id'],
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'sort_order' => $index,
            ]);
        }
    }

    public function deleteByInvoice(int $invoiceId): void
    {
        $this->db->execute('DELETE FROM invoice_line_items WHERE invoice_id = :invoice_id', ['invoice_id' => $invoiceId]);
    }
}

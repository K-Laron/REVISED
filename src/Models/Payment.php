<?php

declare(strict_types=1);

namespace App\Models;

class Payment extends BaseModel
{
    protected static string $table = 'payments';
    protected static bool $useSoftDeletes = false; // Linked to invoice status

    public function paginate(array $filters, int $page, int $perPage): array
    {
        $offset = ($page - 1) * $perPage;
        $clauses = ['1 = 1'];
        $bindings = [];

        if (($filters['search'] ?? '') !== '') {
            $clauses[] = '(p.payment_number LIKE :search OR i.invoice_number LIKE :search OR i.payor_name LIKE :search)';
            $bindings['search'] = '%' . $filters['search'] . '%';
        }

        if (($filters['payment_method'] ?? '') !== '') {
            $clauses[] = 'p.payment_method = :payment_method';
            $bindings['payment_method'] = $filters['payment_method'];
        }

        if (($filters['date_from'] ?? '') !== '') {
            $clauses[] = 'DATE(p.payment_date) >= :date_from';
            $bindings['date_from'] = $filters['date_from'];
        }

        if (($filters['date_to'] ?? '') !== '') {
            $clauses[] = 'DATE(p.payment_date) <= :date_to';
            $bindings['date_to'] = $filters['date_to'];
        }

        $whereSql = 'WHERE ' . implode(' AND ', $clauses);

        $rows = $this->db->fetchAll(
            "SELECT p.*, i.invoice_number, i.payor_name
             FROM payments p
             INNER JOIN invoices i ON i.id = p.invoice_id
             {$whereSql}
             ORDER BY p.payment_date DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $bindings
        );

        $count = $this->db->fetch(
            "SELECT COUNT(*) AS aggregate
             FROM payments p
             INNER JOIN invoices i ON i.id = p.invoice_id
             {$whereSql}",
            $bindings
        );

        return [
            'items' => $rows,
            'total' => (int) ($count['aggregate'] ?? 0),
        ];
    }

    public function listByInvoice(int $invoiceId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM payments
             WHERE invoice_id = :invoice_id
             ORDER BY payment_date DESC, id DESC',
            ['invoice_id' => $invoiceId]
        );
    }

    public function find(int|string $id, bool $includeDeleted = false): array|false
    {
        return $this->db->fetch(
            'SELECT p.*, i.invoice_number, i.payor_name, i.total_amount, i.amount_paid
             FROM payments p
             INNER JOIN invoices i ON i.id = p.invoice_id
             WHERE p.id = :id
             LIMIT 1',
            ['id' => $id]
        );
    }

    public function updateReceiptPath(int $id, string $receiptPath): void
    {
        $this->update($id, ['receipt_path' => $receiptPath]);
    }

    public function listByAnimalAcrossInvoices(int $animalId): array
    {
        return $this->db->fetchAll(
            'SELECT p.*, i.invoice_number
             FROM payments p
             INNER JOIN invoices i ON i.id = p.invoice_id
             WHERE i.animal_id = :animal_id
               AND i.is_deleted = 0
             ORDER BY p.payment_date DESC, p.id DESC',
            ['animal_id' => $animalId]
        );
    }
}

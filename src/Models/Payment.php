<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Payment
{
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

        $rows = Database::fetchAll(
            "SELECT p.*, i.invoice_number, i.payor_name
             FROM payments p
             INNER JOIN invoices i ON i.id = p.invoice_id
             {$whereSql}
             ORDER BY p.payment_date DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $bindings
        );

        $count = Database::fetch(
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
        return Database::fetchAll(
            'SELECT * FROM payments
             WHERE invoice_id = :invoice_id
             ORDER BY payment_date DESC, id DESC',
            ['invoice_id' => $invoiceId]
        );
    }

    public function find(int $id): array|false
    {
        return Database::fetch(
            'SELECT p.*, i.invoice_number, i.payor_name, i.total_amount, i.amount_paid
             FROM payments p
             INNER JOIN invoices i ON i.id = p.invoice_id
             WHERE p.id = :id
             LIMIT 1',
            ['id' => $id]
        );
    }

    public function create(array $data): int
    {
        Database::execute(
            'INSERT INTO payments (
                invoice_id, payment_number, amount, payment_method, reference_number, payment_date,
                receipt_number, receipt_path, notes, received_by
             ) VALUES (
                :invoice_id, :payment_number, :amount, :payment_method, :reference_number, :payment_date,
                :receipt_number, :receipt_path, :notes, :received_by
             )',
            $data
        );

        return (int) Database::lastInsertId();
    }

    public function updateReceiptPath(int $id, string $receiptPath): void
    {
        Database::execute(
            'UPDATE payments SET receipt_path = :receipt_path WHERE id = :id',
            ['id' => $id, 'receipt_path' => $receiptPath]
        );
    }
}

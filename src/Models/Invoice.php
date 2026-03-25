<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Invoice
{
    public function paginate(array $filters, int $page, int $perPage): array
    {
        [$whereSql, $bindings] = $this->buildFilters($filters);
        $offset = ($page - 1) * $perPage;

        $rows = Database::fetchAll(
            "SELECT i.*, a.animal_id AS animal_code, a.name AS animal_name
             FROM invoices i
             LEFT JOIN animals a ON a.id = i.animal_id
             {$whereSql}
             ORDER BY i.created_at DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $bindings
        );

        $count = Database::fetch(
            "SELECT COUNT(*) AS aggregate
             FROM invoices i
             {$whereSql}",
            $bindings
        );

        return [
            'items' => $rows,
            'total' => (int) ($count['aggregate'] ?? 0),
        ];
    }

    public function find(int $id): array|false
    {
        return Database::fetch(
            'SELECT i.*, a.animal_id AS animal_code, a.name AS animal_name
             FROM invoices i
             LEFT JOIN animals a ON a.id = i.animal_id
             WHERE i.id = :id
               AND i.is_deleted = 0
             LIMIT 1',
            ['id' => $id]
        );
    }

    public function create(array $data): int
    {
        Database::execute(
            'INSERT INTO invoices (
                invoice_number, payor_type, payor_user_id, payor_name, payor_contact, payor_address,
                animal_id, application_id, subtotal, tax_amount, total_amount, amount_paid, payment_status,
                issue_date, due_date, notes, terms, pdf_path, created_by, updated_by
             ) VALUES (
                :invoice_number, :payor_type, :payor_user_id, :payor_name, :payor_contact, :payor_address,
                :animal_id, :application_id, :subtotal, :tax_amount, :total_amount, :amount_paid, :payment_status,
                :issue_date, :due_date, :notes, :terms, :pdf_path, :created_by, :updated_by
             )',
            $data
        );

        return (int) Database::lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $data['id'] = $id;

        Database::execute(
            'UPDATE invoices SET
                payor_type = :payor_type,
                payor_user_id = :payor_user_id,
                payor_name = :payor_name,
                payor_contact = :payor_contact,
                payor_address = :payor_address,
                animal_id = :animal_id,
                application_id = :application_id,
                subtotal = :subtotal,
                tax_amount = :tax_amount,
                total_amount = :total_amount,
                payment_status = :payment_status,
                due_date = :due_date,
                notes = :notes,
                terms = :terms,
                pdf_path = :pdf_path,
                updated_by = :updated_by
             WHERE id = :id',
            $data
        );
    }

    public function updateAmounts(int $id, float $amountPaid, string $paymentStatus, ?int $updatedBy): void
    {
        Database::execute(
            'UPDATE invoices
             SET amount_paid = :amount_paid,
                 payment_status = :payment_status,
                 updated_by = :updated_by
             WHERE id = :id',
            [
                'id' => $id,
                'amount_paid' => $amountPaid,
                'payment_status' => $paymentStatus,
                'updated_by' => $updatedBy,
            ]
        );
    }

    public function updatePdfPath(int $id, string $pdfPath): void
    {
        Database::execute(
            'UPDATE invoices SET pdf_path = :pdf_path WHERE id = :id',
            ['id' => $id, 'pdf_path' => $pdfPath]
        );
    }

    public function markVoided(int $id, string $reason, ?int $updatedBy): void
    {
        Database::execute(
            "UPDATE invoices
             SET payment_status = 'void',
                 voided_at = NOW(),
                 voided_reason = :voided_reason,
                 updated_by = :updated_by
             WHERE id = :id",
            [
                'id' => $id,
                'voided_reason' => $reason,
                'updated_by' => $updatedBy,
            ]
        );
    }

    private function buildFilters(array $filters): array
    {
        $clauses = ['i.is_deleted = 0'];
        $bindings = [];

        if (($filters['search'] ?? '') !== '') {
            $clauses[] = '(i.invoice_number LIKE :search OR i.payor_name LIKE :search)';
            $bindings['search'] = '%' . $filters['search'] . '%';
        }

        if (($filters['payment_status'] ?? '') !== '') {
            $clauses[] = 'i.payment_status = :payment_status';
            $bindings['payment_status'] = $filters['payment_status'];
        }

        if (($filters['date_from'] ?? '') !== '') {
            $clauses[] = 'i.issue_date >= :date_from';
            $bindings['date_from'] = $filters['date_from'];
        }

        if (($filters['date_to'] ?? '') !== '') {
            $clauses[] = 'i.issue_date <= :date_to';
            $bindings['date_to'] = $filters['date_to'];
        }

        return ['WHERE ' . implode(' AND ', $clauses), $bindings];
    }
}

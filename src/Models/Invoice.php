<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Pagination\PaginatedWindow;

class Invoice extends BaseModel
{
    protected static string $table = 'invoices';

    public function paginate(array $filters, int $page, int $perPage): array
    {
        [$whereSql, $bindings] = $this->buildFilters($filters);
        $offset = ($page - 1) * $perPage;

        $rows = $this->db->fetchAll(
            "SELECT i.*, a.animal_id AS animal_code, a.name AS animal_name
             FROM invoices i
             LEFT JOIN animals a ON a.id = i.animal_id
             {$whereSql}
             ORDER BY i.created_at DESC
             LIMIT " . ($perPage + 1) . " OFFSET {$offset}",
            $bindings
        );

        return PaginatedWindow::resolve(
            $rows,
            $page,
            $perPage,
            fn (): int => (int) (($this->db->fetch(
                "SELECT COUNT(*) AS aggregate
                 FROM invoices i
                 {$whereSql}",
                $bindings
            )['aggregate'] ?? 0))
        );
    }

    public function getSummaryStats(): array
    {
        return $this->db->fetch(
            "SELECT
                COALESCE(SUM(CASE WHEN i.payment_status = 'paid' AND YEAR(i.issue_date) = YEAR(CURDATE()) AND MONTH(i.issue_date) = MONTH(CURDATE()) THEN i.total_amount ELSE 0 END), 0) AS total_revenue_month,
                COALESCE(SUM(CASE WHEN i.payment_status IN ('unpaid', 'partial') THEN i.balance_due ELSE 0 END), 0) AS outstanding_balance,
                COALESCE(SUM(CASE WHEN DATE(p.payment_date) = CURDATE() THEN p.amount ELSE 0 END), 0) AS paid_today,
                COALESCE(SUM(CASE WHEN i.payment_status IN ('unpaid', 'partial') AND i.due_date < CURDATE() THEN i.balance_due ELSE 0 END), 0) AS overdue_balance,
                COALESCE(SUM(CASE WHEN i.payment_status IN ('unpaid', 'partial') THEN 1 ELSE 0 END), 0) AS outstanding_count,
                COALESCE(SUM(CASE WHEN i.payment_status IN ('unpaid', 'partial') AND i.due_date < CURDATE() THEN 1 ELSE 0 END), 0) AS overdue_count
             FROM invoices i
             LEFT JOIN payments p ON p.invoice_id = i.id
             WHERE i.is_deleted = 0"
        );
    }

    public function find(int|string $id, bool $includeDeleted = false): array|false
    {
        return $this->db->fetch(
            'SELECT i.*, a.animal_id AS animal_code, a.name AS animal_name
             FROM invoices i
             LEFT JOIN animals a ON a.id = i.animal_id
             WHERE i.id = :id
               AND (i.is_deleted = 0 OR :include_deleted = 1)
             LIMIT 1',
            ['id' => $id, 'include_deleted' => $includeDeleted ? 1 : 0]
        );
    }

    public function updateAmounts(int $id, float $amountPaid, string $paymentStatus, ?int $updatedBy): void
    {
        $this->update($id, [
            'amount_paid' => $amountPaid,
            'payment_status' => $paymentStatus,
            'updated_by' => $updatedBy,
        ]);
    }

    public function updatePdfPath(int $id, string $pdfPath): void
    {
        $this->update($id, ['pdf_path' => $pdfPath]);
    }

    public function markVoided(int $id, string $reason, ?int $updatedBy): void
    {
        $this->db->execute(
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

    public function listByAnimal(int $animalId): array
    {
        return $this->db->fetchAll(
            'SELECT *
             FROM invoices
             WHERE animal_id = :animal_id
               AND is_deleted = 0
             ORDER BY issue_date DESC, id DESC',
            ['animal_id' => $animalId]
        );
    }

    public function listByApplication(int $applicationId): array
    {
        return $this->db->fetchAll(
            'SELECT *
             FROM invoices
             WHERE application_id = :application_id
               AND is_deleted = 0
             ORDER BY issue_date DESC, id DESC',
            ['application_id' => $applicationId]
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

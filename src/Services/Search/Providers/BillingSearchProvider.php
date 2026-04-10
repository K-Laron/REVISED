<?php

declare(strict_types=1);

namespace App\Services\Search\Providers;

use App\Core\Database;
use App\Services\Search\AbstractSearchProvider;

final class BillingSearchProvider extends AbstractSearchProvider
{
    public function secondaryFilters(): array
    {
        return [
            'billing_status' => [
                'label' => 'Payment Status',
                'options' => [
                    ['value' => 'unpaid', 'label' => 'Unpaid'],
                    ['value' => 'partial', 'label' => 'Partial'],
                    ['value' => 'paid', 'label' => 'Paid'],
                    ['value' => 'void', 'label' => 'Void'],
                ],
            ],
        ];
    }

    public function legacyStatusAliases(): array
    {
        return [
            'billing_unpaid' => ['key' => 'billing_status', 'value' => 'unpaid'],
            'billing_partial' => ['key' => 'billing_status', 'value' => 'partial'],
            'billing_paid' => ['key' => 'billing_status', 'value' => 'paid'],
            'billing_void' => ['key' => 'billing_status', 'value' => 'void'],
        ];
    }

    public function key(): string
    {
        return 'billing';
    }

    public function label(): string
    {
        return 'Invoices';
    }

    public function permission(): string
    {
        return 'billing.read';
    }

    public function search(string $term, int $limit, array $filters): array
    {
        $bindings = $this->likeBindings($term, 2);
        $filterClause = $this->standardFilterClause((string) ($filters['billing_status'] ?? ''), $filters, 'i.payment_status', 'i.issue_date', 'billing');
        $rows = $this->db->fetchAll(
            "SELECT i.id, i.invoice_number, i.payor_name, i.payment_status, i.total_amount
             FROM invoices i
             WHERE i.is_deleted = 0
               AND (i.invoice_number LIKE :search_1 OR i.payor_name LIKE :search_2)"
               . $filterClause['sql'] . "
             ORDER BY i.created_at DESC, i.id DESC
             LIMIT " . ($limit + 1),
            $bindings + $filterClause['bindings']
        );
        $preview = $this->previewResult(
            $rows,
            $limit,
            fn (): int => (int) (($this->db->fetch(
                "SELECT COUNT(*) AS aggregate
                 FROM invoices i
                 WHERE i.is_deleted = 0
                   AND (i.invoice_number LIKE :search_1 OR i.payor_name LIKE :search_2)"
                   . $filterClause['sql'],
                $bindings + $filterClause['bindings']
            )['aggregate'] ?? 0))
        );

        return $this->section(
            'billing',
            'Invoices',
            '/billing',
            $preview['count'],
            array_map(static fn (array $item): array => [
                'title' => (string) ($item['invoice_number'] ?? ''),
                'subtitle' => (string) ($item['payor_name'] ?? ''),
                'meta' => 'PHP ' . number_format((float) ($item['total_amount'] ?? 0), 2),
                'badge' => (string) ($item['payment_status'] ?? ''),
                'href' => '/billing/invoices/' . (int) $item['id'],
            ], $preview['items'])
        );
    }
}

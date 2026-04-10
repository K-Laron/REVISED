<?php

declare(strict_types=1);

namespace App\Services\Adoption;

use App\Models\Invoice;

class AdoptionBillingSummary
{
    private Invoice $invoices;

    public function __construct(?Invoice $invoices = null)
    {
        $this->invoices = $invoices ?? new Invoice();
    }

    public function linkedInvoices(int $applicationId): array
    {
        return $this->invoices->listByApplication($applicationId);
    }

    public function summarizeForApplication(int $applicationId): array
    {
        return $this->summarize($this->linkedInvoices($applicationId));
    }

    public function summarize(array $invoices): array
    {
        $summary = [
            'invoice_count' => count($invoices),
            'total_amount' => 0.0,
            'amount_paid' => 0.0,
            'balance_due' => 0.0,
            'payment_state' => 'none',
        ];

        if ($invoices === []) {
            return $summary;
        }

        $hasPending = false;
        $allPaid = true;

        foreach ($invoices as $invoice) {
            $summary['total_amount'] += (float) ($invoice['total_amount'] ?? 0);
            $summary['amount_paid'] += (float) ($invoice['amount_paid'] ?? 0);
            $summary['balance_due'] += (float) ($invoice['balance_due'] ?? 0);

            if (($invoice['payment_status'] ?? 'unpaid') !== 'paid') {
                $allPaid = false;
            }

            if (in_array((string) ($invoice['payment_status'] ?? ''), ['unpaid', 'partial'], true)) {
                $hasPending = true;
            }
        }

        $summary['payment_state'] = $allPaid ? 'paid' : ($hasPending ? 'pending' : 'mixed');

        return $summary;
    }
}

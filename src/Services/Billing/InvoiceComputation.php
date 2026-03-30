<?php

declare(strict_types=1);

namespace App\Services\Billing;

class InvoiceComputation
{
    public function normalizeLineItems(array $items): array
    {
        $normalized = [];

        foreach ($items as $item) {
            $description = trim((string) ($item['description'] ?? ''));
            $quantity = (int) ($item['quantity'] ?? 0);
            $unitPrice = round((float) ($item['unit_price'] ?? 0), 2);

            if ($description === '' || $quantity < 1 || $unitPrice <= 0) {
                continue;
            }

            $normalized[] = [
                'fee_schedule_id' => ($item['fee_schedule_id'] ?? '') !== '' ? (int) $item['fee_schedule_id'] : null,
                'description' => $description,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
            ];
        }

        return $normalized;
    }

    public function computeTotals(array $lineItems): array
    {
        $subtotal = 0.0;

        foreach ($lineItems as $item) {
            $subtotal += round($item['quantity'] * $item['unit_price'], 2);
        }

        return [
            'subtotal' => round($subtotal, 2),
            'tax_amount' => 0.0,
            'total_amount' => round($subtotal, 2),
        ];
    }

    public function resolvePaymentStatus(float $amountPaid, float $totalAmount): string
    {
        if ($amountPaid <= 0) {
            return 'unpaid';
        }

        if ($amountPaid >= $totalAmount) {
            return 'paid';
        }

        return 'partial';
    }
}

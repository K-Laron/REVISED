<?php

declare(strict_types=1);

namespace Tests\Services\Adoption;

use App\Services\Adoption\AdoptionBillingSummary;
use PHPUnit\Framework\TestCase;

final class AdoptionBillingSummaryTest extends TestCase
{
    public function testSummarizeReturnsEmptyDefaultsWhenNoInvoicesExist(): void
    {
        $summary = (new AdoptionBillingSummary())->summarize([]);

        self::assertSame(
            [
                'invoice_count' => 0,
                'total_amount' => 0.0,
                'amount_paid' => 0.0,
                'balance_due' => 0.0,
                'payment_state' => 'none',
            ],
            $summary
        );
    }

    public function testSummarizeRecognizesPaidPendingAndMixedStates(): void
    {
        $summary = new AdoptionBillingSummary();

        self::assertSame(
            [
                'invoice_count' => 2,
                'total_amount' => 750.0,
                'amount_paid' => 750.0,
                'balance_due' => 0.0,
                'payment_state' => 'paid',
            ],
            $summary->summarize([
                ['total_amount' => 500, 'amount_paid' => 500, 'balance_due' => 0, 'payment_status' => 'paid'],
                ['total_amount' => 250, 'amount_paid' => 250, 'balance_due' => 0, 'payment_status' => 'paid'],
            ])
        );

        self::assertSame(
            [
                'invoice_count' => 2,
                'total_amount' => 700.0,
                'amount_paid' => 250.0,
                'balance_due' => 450.0,
                'payment_state' => 'pending',
            ],
            $summary->summarize([
                ['total_amount' => 500, 'amount_paid' => 250, 'balance_due' => 250, 'payment_status' => 'partial'],
                ['total_amount' => 200, 'amount_paid' => 0, 'balance_due' => 200, 'payment_status' => 'unpaid'],
            ])
        );

        self::assertSame(
            [
                'invoice_count' => 2,
                'total_amount' => 450.0,
                'amount_paid' => 450.0,
                'balance_due' => 0.0,
                'payment_state' => 'mixed',
            ],
            $summary->summarize([
                ['total_amount' => 300, 'amount_paid' => 300, 'balance_due' => 0, 'payment_status' => 'paid'],
                ['total_amount' => 150, 'amount_paid' => 150, 'balance_due' => 0, 'payment_status' => 'refunded'],
            ])
        );
    }
}

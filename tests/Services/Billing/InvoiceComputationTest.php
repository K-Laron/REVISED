<?php

declare(strict_types=1);

namespace Tests\Services\Billing;

use App\Services\Billing\InvoiceComputation;
use PHPUnit\Framework\TestCase;

final class InvoiceComputationTest extends TestCase
{
    public function testNormalizeLineItemsFiltersInvalidRowsAndRoundsValues(): void
    {
        $computation = new InvoiceComputation();

        self::assertSame(
            [
                [
                    'fee_schedule_id' => 7,
                    'description' => 'Adoption fee',
                    'quantity' => 2,
                    'unit_price' => 120.35,
                ],
                [
                    'fee_schedule_id' => null,
                    'description' => 'Microchip',
                    'quantity' => 1,
                    'unit_price' => 50.0,
                ],
            ],
            $computation->normalizeLineItems([
                ['fee_schedule_id' => '7', 'description' => ' Adoption fee ', 'quantity' => '2', 'unit_price' => '120.349'],
                ['fee_schedule_id' => '', 'description' => 'Microchip', 'quantity' => '1', 'unit_price' => '50'],
                ['fee_schedule_id' => '', 'description' => '', 'quantity' => '1', 'unit_price' => '10'],
                ['fee_schedule_id' => '', 'description' => 'Invalid qty', 'quantity' => '0', 'unit_price' => '10'],
                ['fee_schedule_id' => '', 'description' => 'Invalid price', 'quantity' => '1', 'unit_price' => '0'],
            ])
        );
    }

    public function testComputeTotalsAndPaymentStatusAreConsistent(): void
    {
        $computation = new InvoiceComputation();
        $totals = $computation->computeTotals([
            ['quantity' => 2, 'unit_price' => 120.35],
            ['quantity' => 1, 'unit_price' => 50.0],
        ]);

        self::assertSame(
            [
                'subtotal' => 290.7,
                'tax_amount' => 0.0,
                'total_amount' => 290.7,
            ],
            $totals
        );
        self::assertSame('unpaid', $computation->resolvePaymentStatus(0, 290.7));
        self::assertSame('partial', $computation->resolvePaymentStatus(100, 290.7));
        self::assertSame('paid', $computation->resolvePaymentStatus(290.7, 290.7));
    }
}

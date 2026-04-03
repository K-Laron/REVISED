<?php

declare(strict_types=1);

namespace Tests\Services\Billing;

use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Billing\BillingDocumentManager;
use App\Services\PdfService;
use PHPUnit\Framework\TestCase;

final class BillingDocumentManagerTest extends TestCase
{
    public function testRefreshInvoicePdfRegeneratesAndPersistsPath(): void
    {
        $invoices = $this->createMock(Invoice::class);
        $payments = $this->createMock(Payment::class);
        $pdfs = $this->createMock(PdfService::class);

        $invoice = [
            'id' => 14,
            'invoice_number' => 'INV-0014',
        ];

        $pdfs->expects(self::once())
            ->method('invoice')
            ->with($invoice)
            ->willReturn('storage/pdfs/invoices/INV-0014.pdf');

        $invoices->expects(self::once())
            ->method('updatePdfPath')
            ->with(14, 'storage/pdfs/invoices/INV-0014.pdf');

        $payments->expects(self::never())->method('updateReceiptPath');

        $manager = new BillingDocumentManager($invoices, $payments, $pdfs);

        self::assertSame(
            'storage/pdfs/invoices/INV-0014.pdf',
            $manager->refreshInvoicePdf($invoice)
        );
    }

    public function testRefreshReceiptRegeneratesAndPersistsPath(): void
    {
        $invoices = $this->createMock(Invoice::class);
        $payments = $this->createMock(Payment::class);
        $pdfs = $this->createMock(PdfService::class);

        $payment = [
            'id' => 8,
            'receipt_number' => 'OR-0008',
        ];
        $invoice = [
            'invoice_number' => 'INV-0014',
        ];

        $pdfs->expects(self::once())
            ->method('receipt')
            ->with($payment, $invoice)
            ->willReturn('storage/pdfs/receipts/OR-0008.pdf');

        $payments->expects(self::once())
            ->method('updateReceiptPath')
            ->with(8, 'storage/pdfs/receipts/OR-0008.pdf');

        $invoices->expects(self::never())->method('updatePdfPath');

        $manager = new BillingDocumentManager($invoices, $payments, $pdfs);

        self::assertSame(
            'storage/pdfs/receipts/OR-0008.pdf',
            $manager->refreshReceipt($payment, $invoice)
        );
    }
}

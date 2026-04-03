<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Models\Invoice;
use App\Models\Payment;
use App\Services\PdfService;

class BillingDocumentManager
{
    public function __construct(
        private readonly Invoice $invoices,
        private readonly Payment $payments,
        private readonly PdfService $pdfs
    ) {
    }

    public function refreshInvoicePdf(array $invoice): string
    {
        $pdfPath = $this->pdfs->invoice($invoice);
        $this->invoices->updatePdfPath((int) $invoice['id'], $pdfPath);

        return $pdfPath;
    }

    public function refreshReceipt(array $payment, array $invoice): string
    {
        $receiptPath = $this->pdfs->receipt($payment, $invoice);
        $this->payments->updateReceiptPath((int) $payment['id'], $receiptPath);

        return $receiptPath;
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Request;
use App\Helpers\IdGenerator;
use App\Helpers\Sanitizer;
use App\Models\FeeSchedule;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use App\Models\Payment;
use App\Services\Billing\BillingDocumentManager;
use App\Services\Billing\BillingNotificationDispatcher;
use App\Services\Billing\InvoiceComputation;
use App\Support\InputNormalizer;
use RuntimeException;

class BillingService
{
    private Invoice $invoices;
    private InvoiceLineItem $lineItems;
    private Payment $payments;
    private FeeSchedule $fees;
    private PdfService $pdfs;
    private AuditService $audit;
    private InvoiceComputation $computation;
    private BillingDocumentManager $documents;
    private BillingNotificationDispatcher $notifications;

    public function __construct(
        ?Invoice $invoices = null,
        ?InvoiceLineItem $lineItems = null,
        ?Payment $payments = null,
        ?FeeSchedule $fees = null,
        ?PdfService $pdfs = null,
        ?AuditService $audit = null,
        ?InvoiceComputation $computation = null,
        ?BillingDocumentManager $documents = null,
        ?BillingNotificationDispatcher $notifications = null
    )
    {
        $this->invoices = $invoices ?? new Invoice();
        $this->lineItems = $lineItems ?? new InvoiceLineItem();
        $this->payments = $payments ?? new Payment();
        $this->fees = $fees ?? new FeeSchedule();
        $this->pdfs = $pdfs ?? new PdfService();
        $this->audit = $audit ?? new AuditService();
        $this->computation = $computation ?? new InvoiceComputation();
        $this->documents = $documents ?? new BillingDocumentManager($this->invoices, $this->payments, $this->pdfs);
        $this->notifications = $notifications ?? new BillingNotificationDispatcher(new NotificationService());
    }

    public function listInvoices(array $filters, int $page, int $perPage): array
    {
        return $this->invoices->paginate($filters, $page, $perPage);
    }

    public function listPayments(array $filters, int $page, int $perPage): array
    {
        return $this->payments->paginate($filters, $page, $perPage);
    }

    public function feeSchedule(bool $activeOnly = false): array
    {
        return $this->fees->list($activeOnly);
    }

    public function stats(): array
    {
        $row = Database::fetch(
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

        return [
            'total_revenue_month' => (float) ($row['total_revenue_month'] ?? 0),
            'outstanding_balance' => (float) ($row['outstanding_balance'] ?? 0),
            'paid_today' => (float) ($row['paid_today'] ?? 0),
            'overdue_balance' => (float) ($row['overdue_balance'] ?? 0),
            'outstanding_count' => (int) ($row['outstanding_count'] ?? 0),
            'overdue_count' => (int) ($row['overdue_count'] ?? 0),
        ];
    }

    public function createInvoice(array $data, int $userId, Request $request): array
    {
        $lineItems = $this->computation->normalizeLineItems($data['line_items'] ?? []);
        if ($lineItems === []) {
            throw new RuntimeException('At least one line item is required.');
        }

        $totals = $this->computation->computeTotals($lineItems);
        $invoiceNumber = IdGenerator::next('invoice_number');

        Database::beginTransaction();

        try {
            $invoiceId = $this->invoices->create([
                'invoice_number' => $invoiceNumber,
                'payor_type' => $data['payor_type'],
                'payor_user_id' => ($data['payor_user_id'] ?? '') !== '' ? (int) $data['payor_user_id'] : null,
                'payor_name' => $data['payor_name'],
                'payor_contact' => Sanitizer::phone($data['payor_contact'] ?? null),
                'payor_address' => $data['payor_address'] !== '' ? $data['payor_address'] : null,
                'animal_id' => ($data['animal_id'] ?? '') !== '' ? (int) $data['animal_id'] : null,
                'application_id' => ($data['application_id'] ?? '') !== '' ? (int) $data['application_id'] : null,
                'subtotal' => $totals['subtotal'],
                'tax_amount' => $totals['tax_amount'],
                'total_amount' => $totals['total_amount'],
                'amount_paid' => 0,
                'payment_status' => 'unpaid',
                'issue_date' => date('Y-m-d'),
                'due_date' => $data['due_date'],
                'notes' => $data['notes'] !== '' ? $data['notes'] : null,
                'terms' => $data['terms'] !== '' ? $data['terms'] : null,
                'pdf_path' => null,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            $this->lineItems->createMany($invoiceId, $lineItems);
            Database::commit();
        } catch (\Throwable $exception) {
            Database::rollBack();
            throw $exception;
        }

        $invoice = $this->getInvoice($invoiceId);
        $this->documents->refreshInvoicePdf($invoice);
        $invoice = $this->getInvoice($invoiceId);

        $this->audit->record($userId, 'create', 'billing', 'invoices', $invoiceId, [], $invoice, $request);
        $this->notifications->notifyInvoiceCreated($invoice);

        return $invoice;
    }

    public function updateInvoice(int $invoiceId, array $data, int $userId, Request $request): array
    {
        $current = $this->getInvoice($invoiceId);
        if ($current['voided_at'] !== null) {
            throw new RuntimeException('Voided invoices cannot be edited.');
        }

        $lineItems = $this->computation->normalizeLineItems($data['line_items'] ?? []);
        if ($lineItems === []) {
            throw new RuntimeException('At least one line item is required.');
        }

        $totals = $this->computation->computeTotals($lineItems);
        $amountPaid = (float) $current['amount_paid'];
        $paymentStatus = $this->computation->resolvePaymentStatus($amountPaid, $totals['total_amount']);

        Database::beginTransaction();

        try {
            $this->invoices->update($invoiceId, [
                'payor_type' => $data['payor_type'],
                'payor_user_id' => ($data['payor_user_id'] ?? '') !== '' ? (int) $data['payor_user_id'] : null,
                'payor_name' => $data['payor_name'],
                'payor_contact' => Sanitizer::phone($data['payor_contact'] ?? null),
                'payor_address' => $data['payor_address'] !== '' ? $data['payor_address'] : null,
                'animal_id' => ($data['animal_id'] ?? '') !== '' ? (int) $data['animal_id'] : null,
                'application_id' => ($data['application_id'] ?? '') !== '' ? (int) $data['application_id'] : null,
                'subtotal' => $totals['subtotal'],
                'tax_amount' => $totals['tax_amount'],
                'total_amount' => $totals['total_amount'],
                'payment_status' => $paymentStatus,
                'due_date' => $data['due_date'],
                'notes' => $data['notes'] !== '' ? $data['notes'] : null,
                'terms' => $data['terms'] !== '' ? $data['terms'] : null,
                'pdf_path' => $current['pdf_path'],
                'updated_by' => $userId,
            ]);
            $this->lineItems->deleteByInvoice($invoiceId);
            $this->lineItems->createMany($invoiceId, $lineItems);
            Database::commit();
        } catch (\Throwable $exception) {
            Database::rollBack();
            throw $exception;
        }

        $invoice = $this->getInvoice($invoiceId);
        $this->documents->refreshInvoicePdf($invoice);
        $invoice = $this->getInvoice($invoiceId);

        $this->audit->record($userId, 'update', 'billing', 'invoices', $invoiceId, $current, $invoice, $request);

        return $invoice;
    }

    public function getInvoice(int $invoiceId): array
    {
        $invoice = $this->invoices->find($invoiceId);
        if ($invoice === false) {
            throw new RuntimeException('Invoice not found.');
        }

        $invoice['line_items'] = $this->lineItems->listByInvoice($invoiceId);
        $invoice['payments'] = $this->payments->listByInvoice($invoiceId);

        return $invoice;
    }

    public function voidInvoice(int $invoiceId, string $reason, int $userId, Request $request): array
    {
        $current = $this->getInvoice($invoiceId);
        if ($current['voided_at'] !== null) {
            throw new RuntimeException('Invoice is already voided.');
        }

        $this->invoices->markVoided($invoiceId, $reason, $userId);
        $invoice = $this->getInvoice($invoiceId);
        $this->audit->record($userId, 'delete', 'billing', 'invoices', $invoiceId, $current, ['voided_reason' => $reason], $request);

        return $invoice;
    }

    public function recordPayment(int $invoiceId, array $data, int $userId, Request $request): array
    {
        $invoice = $this->getInvoice($invoiceId);
        if ($invoice['voided_at'] !== null) {
            throw new RuntimeException('Voided invoices cannot accept payments.');
        }

        $amount = round((float) $data['amount'], 2);
        if ($amount <= 0) {
            throw new RuntimeException('Payment amount must be greater than zero.');
        }

        $balanceDue = round((float) $invoice['total_amount'] - (float) $invoice['amount_paid'], 2);
        if ($amount > $balanceDue) {
            throw new RuntimeException('Payment amount cannot exceed the balance due.');
        }

        $paymentNumber = IdGenerator::next('payment_number');
        $receiptNumber = 'OR-' . substr($paymentNumber, 4);

        Database::beginTransaction();

        try {
            $paymentId = $this->payments->create([
                'invoice_id' => $invoiceId,
                'payment_number' => $paymentNumber,
                'amount' => $amount,
                'payment_method' => $data['payment_method'],
                'reference_number' => $data['reference_number'] !== '' ? $data['reference_number'] : null,
                'payment_date' => str_contains((string) $data['payment_date'], 'T')
                    ? str_replace('T', ' ', (string) $data['payment_date']) . ':00'
                    : (string) $data['payment_date'] . ' 00:00:00',
                'receipt_number' => $receiptNumber,
                'receipt_path' => null,
                'notes' => $data['notes'] !== '' ? $data['notes'] : null,
                'received_by' => $userId,
            ]);

            $newAmountPaid = round((float) $invoice['amount_paid'] + $amount, 2);
            $status = $this->computation->resolvePaymentStatus($newAmountPaid, (float) $invoice['total_amount']);
            $this->invoices->updateAmounts($invoiceId, $newAmountPaid, $status, $userId);
            Database::commit();
        } catch (\Throwable $exception) {
            Database::rollBack();
            throw $exception;
        }

        $updatedInvoice = $this->getInvoice($invoiceId);
        $payment = $this->payments->find($paymentId);
        if ($payment === false) {
            throw new RuntimeException('Payment record was not created.');
        }

        $this->documents->refreshReceipt($payment, $updatedInvoice);
        $payment = $this->payments->find($paymentId);

        $this->audit->record($userId, 'create', 'billing', 'payments', $paymentId, [], $payment ?: [], $request);

        return [
            'invoice' => $this->getInvoice($invoiceId),
            'payment' => $payment,
        ];
    }

    public function getPayment(int $paymentId): array
    {
        $payment = $this->payments->find($paymentId);
        if ($payment === false) {
            throw new RuntimeException('Payment not found.');
        }

        return $payment;
    }

    public function storeFee(array $data, int $userId, Request $request): array
    {
        $feeId = $this->fees->create([
            'category' => $data['category'],
            'name' => $data['name'],
            'description' => $data['description'] !== '' ? $data['description'] : null,
            'amount' => round((float) $data['amount'], 2),
            'is_per_day' => InputNormalizer::bool($data['is_per_day'] ?? false) ? 1 : 0,
            'species_filter' => ($data['species_filter'] ?? '') !== '' ? $data['species_filter'] : null,
            'effective_from' => $data['effective_from'],
            'effective_to' => ($data['effective_to'] ?? '') !== '' ? $data['effective_to'] : null,
            'is_active' => InputNormalizer::bool($data['is_active'] ?? true) ? 1 : 0,
            'created_by' => $userId,
        ]);

        $fee = $this->fees->find($feeId);
        $this->audit->record($userId, 'create', 'billing', 'fee_schedule', $feeId, [], $fee ?: [], $request);

        if ($fee === false) {
            throw new RuntimeException('Fee schedule item was not created.');
        }

        return $fee;
    }

    public function updateFee(int $feeId, array $data, int $userId, Request $request): array
    {
        $current = $this->fees->find($feeId);
        if ($current === false) {
            throw new RuntimeException('Fee item not found.');
        }

        $this->fees->update($feeId, [
            'category' => $data['category'],
            'name' => $data['name'],
            'description' => $data['description'] !== '' ? $data['description'] : null,
            'amount' => round((float) $data['amount'], 2),
            'is_per_day' => InputNormalizer::bool($data['is_per_day'] ?? false) ? 1 : 0,
            'species_filter' => ($data['species_filter'] ?? '') !== '' ? $data['species_filter'] : null,
            'effective_from' => $data['effective_from'],
            'effective_to' => ($data['effective_to'] ?? '') !== '' ? $data['effective_to'] : null,
            'is_active' => InputNormalizer::bool($data['is_active'] ?? true) ? 1 : 0,
        ]);

        $fee = $this->fees->find($feeId);
        $this->audit->record($userId, 'update', 'billing', 'fee_schedule', $feeId, $current, $fee ?: [], $request);

        if ($fee === false) {
            throw new RuntimeException('Fee item not found after update.');
        }

        return $fee;
    }

}

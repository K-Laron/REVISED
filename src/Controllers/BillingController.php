<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\Concerns\InteractsWithApi;
use App\Controllers\Concerns\RendersViews;
use App\Core\Request;
use App\Core\Response;
use App\Helpers\Validator;
use App\Middleware\CsrfMiddleware;
use App\Services\BillingService;
use App\Support\Pagination;
use RuntimeException;

class BillingController
{
    use InteractsWithApi;
    use RendersViews;

    private BillingService $billing;

    public function __construct()
    {
        $this->billing = new BillingService();
    }

    public function index(Request $request): Response
    {
        return $this->renderAppView('billing.index', [
            'title' => 'Billing & Invoicing',
            'extraCss' => ['/assets/css/billing.css'],
            'extraJs' => ['/assets/js/billing.js'],
            'csrfToken' => CsrfMiddleware::token(),
            'fees' => $this->billing->feeSchedule(),
        ]);
    }

    public function createInvoice(Request $request): Response
    {
        return $this->renderAppView('billing.create-invoice', [
            'title' => 'Create Invoice',
            'extraCss' => ['/assets/css/billing.css'],
            'extraJs' => ['/assets/js/billing.js'],
            'csrfToken' => CsrfMiddleware::token(),
            'fees' => $this->billing->feeSchedule(true),
        ]);
    }

    public function showInvoice(Request $request, string $id): Response
    {
        try {
            $invoice = $this->billing->getInvoice((int) $id);
        } catch (RuntimeException) {
            return Response::redirect('/billing');
        }

        return $this->renderAppView('billing.show-invoice', [
            'title' => $invoice['invoice_number'],
            'extraCss' => ['/assets/css/billing.css'],
            'extraJs' => ['/assets/js/billing.js'],
            'csrfToken' => CsrfMiddleware::token(),
            'invoice' => $invoice,
        ]);
    }

    public function listInvoices(Request $request): Response
    {
        $page = Pagination::page($request->query('page'));
        $perPage = Pagination::perPage($request->query('per_page'), 20);
        $result = $this->billing->listInvoices($request->query(), $page, $perPage);

        return $this->paginatedSuccess($result, $page, $perPage, 'Invoices retrieved successfully.');
    }

    public function storeInvoice(Request $request): Response
    {
        $validator = (new Validator($request->body()))->rules([
            'payor_type' => 'required|in:adopter,owner,external',
            'payor_user_id' => 'nullable|integer|exists:users,id',
            'payor_name' => 'required|string|max:200',
            'payor_contact' => 'nullable|phone_ph',
            'payor_address' => 'nullable|string|max:500',
            'animal_id' => 'nullable|integer|exists:animals,id',
            'application_id' => 'nullable|integer',
            'due_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
            'terms' => 'nullable|string|max:1000',
            'line_items' => 'required|array|min:1',
        ]);

        $lineItemErrors = $this->lineItemErrors($request->body('line_items', []));
        if ($validator->fails() || $lineItemErrors !== []) {
            return $this->validationError($validator->errors() + $lineItemErrors);
        }

        $authUserId = $this->currentUserId($request);

        try {
            $invoice = $this->billing->createInvoice($request->body(), $authUserId, $request);
        } catch (\Throwable $exception) {
            return Response::error(500, 'SERVER_ERROR', $exception->getMessage());
        }

        return Response::success([
            'invoice' => $invoice,
            'redirect' => '/billing/invoices/' . $invoice['id'],
        ], 'Invoice created successfully.');
    }

    public function updateInvoice(Request $request, string $id): Response
    {
        $validator = (new Validator($request->body()))->rules([
            'payor_type' => 'required|in:adopter,owner,external',
            'payor_user_id' => 'nullable|integer|exists:users,id',
            'payor_name' => 'required|string|max:200',
            'payor_contact' => 'nullable|phone_ph',
            'payor_address' => 'nullable|string|max:500',
            'animal_id' => 'nullable|integer|exists:animals,id',
            'application_id' => 'nullable|integer',
            'due_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
            'terms' => 'nullable|string|max:1000',
            'line_items' => 'required|array|min:1',
        ]);

        $lineItemErrors = $this->lineItemErrors($request->body('line_items', []));
        if ($validator->fails() || $lineItemErrors !== []) {
            return $this->validationError($validator->errors() + $lineItemErrors);
        }

        $authUserId = $this->currentUserId($request);

        try {
            $invoice = $this->billing->updateInvoice((int) $id, $request->body(), $authUserId, $request);
        } catch (RuntimeException $exception) {
            return Response::error(409, 'INVOICE_UPDATE_BLOCKED', $exception->getMessage());
        }

        return Response::success([
            'invoice' => $invoice,
            'redirect' => '/billing/invoices/' . $invoice['id'],
        ], 'Invoice updated successfully.');
    }

    public function voidInvoice(Request $request, string $id): Response
    {
        $validator = (new Validator($request->body()))->rules([
            'voided_reason' => 'required|string|min:5|max:1000',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $authUserId = $this->currentUserId($request);

        try {
            $invoice = $this->billing->voidInvoice((int) $id, (string) $request->body('voided_reason'), $authUserId, $request);
        } catch (RuntimeException $exception) {
            return Response::error(409, 'INVOICE_VOID_BLOCKED', $exception->getMessage());
        }

        return Response::success($invoice, 'Invoice voided successfully.');
    }

    public function invoicePdf(Request $request, string $id): Response
    {
        try {
            $invoice = $this->billing->getInvoice((int) $id);
        } catch (RuntimeException) {
            return Response::error(404, 'NOT_FOUND', 'Invoice not found.');
        }

        $path = dirname(__DIR__, 2) . '/' . $invoice['pdf_path'];
        if (!is_file($path)) {
            return Response::error(404, 'NOT_FOUND', 'Invoice PDF not found.');
        }

        return $this->fileDownloadResponse($path, 'application/pdf', $invoice['invoice_number'] . '.pdf');
    }

    public function recordPayment(Request $request, string $id): Response
    {
        $validator = (new Validator($request->body()))->rules([
            'amount' => 'required|numeric|between:0.01,999999',
            'payment_method' => 'required|in:Cash,Bank Transfer,GCash,Maya,Check',
            'reference_number' => 'nullable|string|max:100',
            'payment_date' => 'required|string',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $authUserId = $this->currentUserId($request);

        try {
            $result = $this->billing->recordPayment((int) $id, $request->body(), $authUserId, $request);
        } catch (RuntimeException $exception) {
            return Response::error(409, 'PAYMENT_RECORD_BLOCKED', $exception->getMessage());
        }

        return Response::success($result, 'Payment recorded successfully.');
    }

    public function listPayments(Request $request): Response
    {
        $page = Pagination::page($request->query('page'));
        $perPage = Pagination::perPage($request->query('per_page'), 20);
        $result = $this->billing->listPayments($request->query(), $page, $perPage);

        return $this->paginatedSuccess($result, $page, $perPage, 'Payments retrieved successfully.');
    }

    public function receiptPdf(Request $request, string $id): Response
    {
        try {
            $payment = $this->billing->getPayment((int) $id);
        } catch (RuntimeException) {
            return Response::error(404, 'NOT_FOUND', 'Payment not found.');
        }

        $path = dirname(__DIR__, 2) . '/' . $payment['receipt_path'];
        if (!is_file($path)) {
            return Response::error(404, 'NOT_FOUND', 'Receipt PDF not found.');
        }

        return $this->fileDownloadResponse(
            $path,
            'application/pdf',
            ($payment['receipt_number'] ?: $payment['payment_number']) . '.pdf'
        );
    }

    public function feeSchedule(Request $request): Response
    {
        return Response::success($this->billing->feeSchedule(), 'Fee schedule retrieved successfully.');
    }

    public function storeFee(Request $request): Response
    {
        $validator = (new Validator($request->body()))->rules([
            'category' => 'required|string|max:50',
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:2000',
            'amount' => 'required|numeric|between:0.01,999999',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date',
            'species_filter' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $fee = $this->billing->storeFee($request->body(), $this->currentUserId($request), $request);

        return Response::success($fee, 'Fee item created successfully.');
    }

    public function updateFee(Request $request, string $id): Response
    {
        $validator = (new Validator($request->body()))->rules([
            'category' => 'required|string|max:50',
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:2000',
            'amount' => 'required|numeric|between:0.01,999999',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date',
            'species_filter' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $authUserId = $this->currentUserId($request);

        try {
            $fee = $this->billing->updateFee((int) $id, $request->body(), $authUserId, $request);
        } catch (RuntimeException $exception) {
            return Response::error(404, 'NOT_FOUND', $exception->getMessage());
        }

        return Response::success($fee, 'Fee item updated successfully.');
    }

    public function stats(Request $request): Response
    {
        return Response::success($this->billing->stats(), 'Billing stats retrieved successfully.');
    }

    private function lineItemErrors(array $lineItems): array
    {
        $errors = [];

        if (!is_array($lineItems) || $lineItems === []) {
            $errors['line_items'][] = 'At least one line item is required.';
            return $errors;
        }

        foreach ($lineItems as $index => $item) {
            if (trim((string) ($item['description'] ?? '')) === '') {
                $errors["line_items.{$index}.description"][] = 'Description is required.';
            }

            if ((int) ($item['quantity'] ?? 0) < 1) {
                $errors["line_items.{$index}.quantity"][] = 'Quantity must be at least 1.';
            }

            if ((float) ($item['unit_price'] ?? 0) <= 0) {
                $errors["line_items.{$index}.unit_price"][] = 'Unit price must be greater than 0.';
            }
        }

        return $errors;
    }
}

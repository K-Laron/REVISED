<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Services\NotificationService;

class BillingNotificationDispatcher
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    public function notifyInvoiceCreated(array $invoice): void
    {
        $this->notifications->notifyRole('billing_clerk', [
            'type' => 'info',
            'title' => 'New Invoice Created',
            'message' => 'Invoice ' . (string) $invoice['invoice_number'] . ' is pending payment.',
            'link' => '/billing/invoices/' . (int) $invoice['id'],
        ]);
    }
}

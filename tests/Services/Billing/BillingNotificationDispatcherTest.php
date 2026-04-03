<?php

declare(strict_types=1);

namespace Tests\Services\Billing;

use App\Services\Billing\BillingNotificationDispatcher;
use App\Services\NotificationService;
use PHPUnit\Framework\TestCase;

final class BillingNotificationDispatcherTest extends TestCase
{
    public function testNotifyInvoiceCreatedTargetsBillingClerkRole(): void
    {
        $notifications = $this->createMock(NotificationService::class);
        $invoice = [
            'id' => 14,
            'invoice_number' => 'INV-0014',
        ];

        $notifications->expects(self::once())
            ->method('notifyRole')
            ->with(
                'billing_clerk',
                [
                    'type' => 'info',
                    'title' => 'New Invoice Created',
                    'message' => 'Invoice INV-0014 is pending payment.',
                    'link' => '/billing/invoices/14',
                ]
            );

        $dispatcher = new BillingNotificationDispatcher($notifications);
        $dispatcher->notifyInvoiceCreated($invoice);
    }
}

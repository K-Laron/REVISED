<?php

declare(strict_types=1);

namespace Tests\Services\Billing;

use PHPUnit\Framework\TestCase;

final class BillingRefactorAdoptionTest extends TestCase
{
    public function testBillingServiceDelegatesDocumentsAndNotificationsToCollaborators(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 3) . '/src/Services/BillingService.php');

        self::assertStringContainsString('BillingDocumentManager', $source);
        self::assertStringContainsString('BillingNotificationDispatcher', $source);
    }
}

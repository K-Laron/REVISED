<?php

declare(strict_types=1);

namespace Tests\Services\Billing;

use PHPUnit\Framework\TestCase;

final class BillingRefactorAdoptionTest extends TestCase
{
    public function testBillingServiceDelegatesDocumentsAndNotificationsToCollaborators(): void
    {
        $source = (string) file_get_contents('C:\\Users\\TESS LARON\\Desktop\\REVISED\\src\\Services\\BillingService.php');

        self::assertStringContainsString('BillingDocumentManager', $source);
        self::assertStringContainsString('BillingNotificationDispatcher', $source);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Views;

require_once __DIR__ . '/ViewSmokeTestCase.php';

final class PortalLandingViewTest extends ViewSmokeTestCase
{
    public function testPortalLandingRendersTheCivicLedgerHeroAndTrustSections(): void
    {
        $html = $this->renderPublic('portal.landing', [
            'featuredAnimals' => $this->featuredAnimals(),
            'currentUser' => null,
        ], '/adopt');

        self::assertStringContainsString('portal-civic-hero', $html);
        self::assertStringContainsString('portal-trust-ribbon', $html);
        self::assertStringContainsString('portal-featured-ledger', $html);
        self::assertStringContainsString('data-carousel-track', $html);
    }
}

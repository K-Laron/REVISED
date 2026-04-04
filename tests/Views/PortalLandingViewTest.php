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

    public function testPortalLandingStylesDeclareDarkThemeSurfaceOverrides(): void
    {
        $root = dirname(__DIR__, 2);
        $variables = (string) file_get_contents($root . '/public/assets/css/variables.css');
        $stylesheet = (string) file_get_contents($root . '/public/assets/css/portal.css');

        self::assertStringContainsString('--color-bg-warm: rgba(', $variables);
        self::assertStringContainsString('[data-theme="dark"] .portal-proof-card,', $stylesheet);
        self::assertStringContainsString('[data-theme="dark"] .portal-featured-ledger', $stylesheet);
        self::assertStringContainsString('[data-theme="dark"] .portal-trust-ribbon .portal-landing-trust-item', $stylesheet);
    }
}

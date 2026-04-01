<?php

declare(strict_types=1);

namespace Tests\Views;

require_once __DIR__ . '/ViewSmokeTestCase.php';

final class AppShellViewTest extends ViewSmokeTestCase
{
    public function testAppLayoutLoadsCivicLedgerFontsAndThemeMarker(): void
    {
        $html = $this->renderApp('dashboard.index');

        self::assertStringContainsString('Lexend', $html);
        self::assertStringContainsString('Source+Sans+3', $html);
        self::assertStringContainsString('JetBrains+Mono', $html);
        self::assertStringContainsString('data-ui-theme="civic-ledger"', $html);
    }

    public function testPublicLayoutKeepsSkipLinkAndUsesTheSameFontStack(): void
    {
        $html = $this->renderPublic('portal.landing', [
            'featuredAnimals' => $this->featuredAnimals(),
        ]);

        self::assertStringContainsString('href="#public-main"', $html);
        self::assertStringContainsString('data-ui-theme="civic-ledger"', $html);
        self::assertStringContainsString('JetBrains+Mono', $html);
    }
}

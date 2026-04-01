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

    public function testAppLayoutRendersTheNewCommandRailAndHeaderShell(): void
    {
        $html = $this->renderApp('dashboard.index');

        self::assertStringContainsString('sidebar-rail-summary', $html);
        self::assertStringContainsString('sidebar-group-card', $html);
        self::assertStringContainsString('topbar-command-shell', $html);
        self::assertStringContainsString('topbar-status-pill', $html);
    }

    public function testLayoutsDoNotLoadLegacyFiraFonts(): void
    {
        $appHtml = $this->renderApp('dashboard.index');
        $portalHtml = $this->renderPublic('portal.landing', [
            'featuredAnimals' => $this->featuredAnimals(),
        ]);

        self::assertStringNotContainsString('Fira+Sans', $appHtml);
        self::assertStringNotContainsString('Fira+Code', $appHtml);
        self::assertStringNotContainsString('Fira+Sans', $portalHtml);
        self::assertStringNotContainsString('Fira+Code', $portalHtml);
    }
}

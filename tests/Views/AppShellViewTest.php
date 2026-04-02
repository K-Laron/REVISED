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

    public function testAuthenticatedLayoutLoadsSharedBreadcrumbDraftProtection(): void
    {
        $html = $this->renderApp('billing.create-invoice', [
            'fees' => [],
        ], '/billing/invoices/create');

        self::assertStringContainsString('/assets/js/core/app-breadcrumbs.js', $html);
        self::assertStringContainsString('data-breadcrumb-link="true"', $html);
        self::assertStringContainsString('href="/dashboard"', $html);
        self::assertStringContainsString('href="/billing"', $html);
    }

    public function testAuthenticatedLayoutUsesThemeTokensForInitialShellPaint(): void
    {
        $html = $this->renderApp('dashboard.index');

        self::assertStringContainsString('background-color: var(--color-bg-primary);', $html);
        self::assertStringContainsString('background-image: linear-gradient(180deg, var(--color-bg-primary) 0%, var(--color-bg-secondary) 100%);', $html);
        self::assertStringContainsString('color: var(--color-text-primary);', $html);
        self::assertStringNotContainsString("background-color: #f8fafc;", $html);
    }

    public function testDashboardStylesDeclareThemeAwareSurfaceOverrides(): void
    {
        $stylesheet = (string) file_get_contents('C:\\Users\\TESS LARON\\Desktop\\REVISED\\public\\assets\\css\\dashboard.css');

        self::assertStringContainsString('[data-theme="dark"] .dashboard-briefing-hero', $stylesheet);
        self::assertStringContainsString('[data-theme="dark"] .stat-card', $stylesheet);
        self::assertStringContainsString('[data-theme="light"] .dashboard-action-deck', $stylesheet);
    }

    public function testAuthenticatedLayoutIncludesSidebarScrollPersistenceHook(): void
    {
        $html = $this->renderApp('dashboard.index');

        self::assertStringContainsString('data-sidebar-scroll-region', $html);
    }

    public function testSharedShellScriptPersistsSidebarScrollState(): void
    {
        $script = (string) file_get_contents('C:\\Users\\TESS LARON\\Desktop\\REVISED\\public\\assets\\js\\core\\app-shell.js');

        self::assertStringContainsString('catarman:sidebar-scroll-top', $script);
        self::assertStringContainsString('sessionStorage', $script);
        self::assertStringContainsString('data-sidebar-scroll-region', $script);
    }

    public function testNotificationDropdownScriptHidesReadItemsAfterRefresh(): void
    {
        $script = (string) file_get_contents('C:\\Users\\TESS LARON\\Desktop\\REVISED\\public\\assets\\js\\notifications.js');

        self::assertStringContainsString('filter((item) => !item.is_read)', $script);
        self::assertStringContainsString('No unread notifications.', $script);
    }
}

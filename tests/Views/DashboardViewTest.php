<?php

declare(strict_types=1);

namespace Tests\Views;

require_once __DIR__ . '/ViewSmokeTestCase.php';

final class DashboardViewTest extends ViewSmokeTestCase
{
    public function testDashboardRendersTheBriefingLayoutMarkers(): void
    {
        $html = $this->renderApp('dashboard.index', [
            'title' => 'Dashboard',
            'csrfToken' => 'test-token',
        ]);

        self::assertStringContainsString('dashboard-briefing', $html);
        self::assertStringContainsString('dashboard-kpi-grid', $html);
        self::assertStringContainsString('dashboard-action-deck', $html);
        self::assertStringContainsString('dashboard-activity-feed', $html);
    }

    public function testDashboardScriptPrefersTheBootstrapEndpoint(): void
    {
        $script = (string) file_get_contents(dirname(__DIR__, 2) . '/public/assets/js/dashboard.js');

        self::assertStringContainsString('/api/dashboard/bootstrap', $script);
    }

    public function testDashboardRendersEnhancedOccupancyChartShell(): void
    {
        $html = $this->renderApp('dashboard.index', [
            'title' => 'Dashboard',
            'csrfToken' => 'test-token',
        ]);

        self::assertStringContainsString('data-occupancy-shell', $html);
        self::assertStringContainsString('id="occupancy-breakdown"', $html);
        self::assertStringContainsString('data-occupancy-summary', $html);
    }

    public function testDashboardRendersIntakeExecutiveSummaryShell(): void
    {
        $html = $this->renderApp('dashboard.index', [
            'title' => 'Dashboard',
            'csrfToken' => 'test-token',
        ]);

        self::assertStringContainsString('data-intake-summary-shell', $html);
        self::assertStringContainsString('id="intake-summary-metrics"', $html);
        self::assertStringContainsString('id="intake-summary-insight"', $html);
        self::assertStringContainsString('dashboard-intake-stage', $html);
    }

    public function testDashboardScriptDeclaresOccupancyChartEnhancements(): void
    {
        $script = (string) file_get_contents(dirname(__DIR__, 2) . '/public/assets/js/dashboard.js');

        self::assertStringContainsString('occupancy-breakdown', $script);
        self::assertStringContainsString('occupancyCenterLabel', $script);
        self::assertStringContainsString('cutout', $script);
    }

    public function testDashboardScriptDeclaresExecutiveIntakeSummaryHelpers(): void
    {
        $script = (string) file_get_contents(dirname(__DIR__, 2) . '/public/assets/js/dashboard.js');

        self::assertStringContainsString('deriveIntakeSummaryModel', $script);
        self::assertStringContainsString('renderIntakeSummary', $script);
        self::assertStringContainsString('data-intake-summary-shell', $script);
        self::assertStringContainsString('intake-summary-insight', $script);
    }

    public function testDashboardRendersActivityDigestShell(): void
    {
        $html = $this->renderApp('dashboard.index', [
            'title' => 'Dashboard',
            'csrfToken' => 'test-token',
        ]);

        self::assertStringContainsString('data-activity-shell', $html);
        self::assertStringContainsString('id="activity-digest"', $html);
        self::assertStringContainsString('data-activity-digest', $html);
    }

    public function testDashboardScriptDeclaresActivityDigestRenderer(): void
    {
        $script = (string) file_get_contents(dirname(__DIR__, 2) . '/public/assets/js/dashboard.js');

        self::assertStringContainsString('renderActivityDigest', $script);
        self::assertStringContainsString('activity-digest', $script);
        self::assertStringContainsString('ACTIVITY_FEED_LIMIT', $script);
    }

    public function testDashboardScriptReusesLoadedPayloadForThemeRefresh(): void
    {
        $script = (string) file_get_contents(dirname(__DIR__, 2) . '/public/assets/js/dashboard.js');

        self::assertStringContainsString('let dashboardPayload = null;', $script);
        self::assertStringContainsString('dashboardPayload = payload;', $script);
        self::assertStringContainsString('renderDashboard(dashboardPayload);', $script);
    }

    public function testDashboardRendersActionQueueCardsWhenProvided(): void
    {
        $html = $this->renderApp('dashboard.index', [
            'title' => 'Dashboard',
            'csrfToken' => 'test-token',
            'actionQueue' => [[
                'label' => 'Low stock needs review',
                'count' => 3,
                'urgency' => 'High',
                'summary' => 'Three inventory items are at or below reorder level.',
                'href' => '/inventory',
            ]],
        ]);

        self::assertStringContainsString('dashboard-action-queue', $html);
        self::assertStringContainsString('Low stock needs review', $html);
        self::assertStringContainsString('/inventory', $html);
    }

    public function testDashboardPlacesSupportingChartsBeforeRecentActivity(): void
    {
        $html = $this->renderApp('dashboard.index', [
            'title' => 'Dashboard',
            'csrfToken' => 'test-token',
        ]);

        $supportingChartsPosition = strpos($html, 'Adoption Pipeline');
        $recentActivityPosition = strpos($html, 'Recent Activity');

        self::assertIsInt($supportingChartsPosition);
        self::assertIsInt($recentActivityPosition);
        self::assertLessThan($recentActivityPosition, $supportingChartsPosition);
    }
}

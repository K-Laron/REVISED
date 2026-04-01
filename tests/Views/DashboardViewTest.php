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
}

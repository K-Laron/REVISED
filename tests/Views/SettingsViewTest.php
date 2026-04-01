<?php

declare(strict_types=1);

namespace Tests\Views;

require_once __DIR__ . '/ViewSmokeTestCase.php';

final class SettingsViewTest extends ViewSmokeTestCase
{
    public function testSettingsPageRendersTheOperationsConsoleMarkers(): void
    {
        $html = $this->renderApp('settings.index', [
            'title' => 'Settings',
            'currentUser' => $this->defaultUser(),
            'canManageSystem' => true,
            'settingsMeta' => [
                'app_name' => 'Catarman Animal Shelter',
                'organization_name' => 'Catarman Dog Pound',
                'settings_storage_driver' => 'database',
                'app_env' => 'local',
                'app_url' => 'http://localhost:8000',
                'app_timezone' => 'Asia/Manila',
                'session_lifetime' => 60,
                'trusted_proxies' => '',
                'public_portal_enabled' => true,
                'maintenance_mode_enabled' => false,
                'maintenance_message' => '',
                'contact_email' => 'ops@example.test',
                'contact_phone' => '09171234567',
                'office_address' => 'Catarman, Northern Samar',
                'mail_delivery_mode' => 'log_only',
            ],
            'extraCss' => ['/assets/css/settings.css'],
            'extraJs' => ['/assets/js/settings.js'],
        ], '/settings');

        self::assertStringContainsString('settings-ops-hero', $html);
        self::assertStringContainsString('settings-zone-grid', $html);
        self::assertStringContainsString('settings-backup-ledger', $html);
        self::assertStringContainsString('settings-readiness-board', $html);
    }
}

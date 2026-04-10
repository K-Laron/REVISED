<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\Concerns\InteractsWithApi;
use App\Controllers\Concerns\RendersViews;
use App\Core\Request;
use App\Core\Response;
use App\Middleware\CsrfMiddleware;
use App\Services\SystemSettingsService;
use App\Support\SystemSettings;

class SettingsController
{
    use InteractsWithApi;
    use RendersViews;

    public function __construct(
        private readonly SystemSettingsService $settingsService,
        private readonly SystemSettings $systemSettings
    ) {
    }

    public function index(Request $request): Response
    {
        $authUser = $this->currentUser($request);
        $isSuperAdmin = (($authUser['role_name'] ?? null) === 'super_admin');
        $settings = $this->settingsService->settings();

        return $this->renderAppView('settings.index', [
            'title' => 'Settings',
            'extraCss' => ['/assets/css/settings.css'],
            'extraJs' => ['/assets/js/settings.js'],
            'csrfToken' => CsrfMiddleware::token(),
            'currentUser' => $authUser,
            'canManageSystem' => $isSuperAdmin,
            'settingsMeta' => $settings + [
                'settings_storage_driver' => $this->systemSettings->storageDriver(),
                'app_env' => $_ENV['APP_ENV'] ?? 'production',
                'app_url' => $_ENV['APP_URL'] ?? '',
                'app_timezone' => $_ENV['APP_TIMEZONE'] ?? 'Asia/Manila',
                'session_lifetime' => (int) ($_ENV['SESSION_LIFETIME'] ?? 60),
                'trusted_proxies' => $_ENV['TRUSTED_PROXIES'] ?? '',
            ],
        ]);
    }
}

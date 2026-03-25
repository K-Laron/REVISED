<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Middleware\CsrfMiddleware;
use App\Services\SystemSettingsService;
use App\Support\SystemSettings;

class SettingsController
{
    private SystemSettingsService $settings;

    public function __construct()
    {
        $this->settings = new SystemSettingsService();
    }

    public function index(Request $request): Response
    {
        $authUser = $request->attribute('auth_user');
        $isSuperAdmin = (($authUser['role_name'] ?? null) === 'super_admin');
        $settings = $this->settings->settings();

        return Response::html(View::render('settings.index', [
            'title' => 'Settings',
            'extraCss' => ['/assets/css/settings.css'],
            'extraJs' => ['/assets/js/settings.js'],
            'csrfToken' => CsrfMiddleware::token(),
            'currentUser' => $authUser,
            'canManageSystem' => $isSuperAdmin,
            'settingsMeta' => $settings + [
                'settings_storage_driver' => SystemSettings::storageDriver(),
                'app_env' => $_ENV['APP_ENV'] ?? 'production',
                'app_url' => $_ENV['APP_URL'] ?? '',
                'app_timezone' => $_ENV['APP_TIMEZONE'] ?? 'Asia/Manila',
                'session_lifetime' => (int) ($_ENV['SESSION_LIFETIME'] ?? 60),
                'trusted_proxies' => $_ENV['TRUSTED_PROXIES'] ?? '',
            ],
        ], 'layouts.app'));
    }
}

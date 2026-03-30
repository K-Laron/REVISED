<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Request;
use App\Support\InputNormalizer;
use App\Support\SystemSettings;
use Throwable;

class SystemSettingsService
{
    private const RECOMMENDED_SESSION_LIFETIME_MINUTES = 60;

    private AuditService $audit;

    public function __construct()
    {
        $this->audit = new AuditService();
    }

    public function settings(): array
    {
        return SystemSettings::all();
    }

    public function update(array $data, int $userId, ?Request $request = null): array
    {
        $current = $this->settings();
        $updated = SystemSettings::save([
            'app_name' => trim((string) ($data['app_name'] ?? $current['app_name'])),
            'organization_name' => trim((string) ($data['organization_name'] ?? $current['organization_name'])),
            'public_portal_enabled' => InputNormalizer::bool($data['public_portal_enabled'] ?? $current['public_portal_enabled']),
            'contact_email' => trim((string) ($data['contact_email'] ?? $current['contact_email'])),
            'contact_phone' => trim((string) ($data['contact_phone'] ?? $current['contact_phone'])),
            'office_address' => trim((string) ($data['office_address'] ?? $current['office_address'])),
            'mail_delivery_mode' => $this->mailMode($data['mail_delivery_mode'] ?? $current['mail_delivery_mode'] ?? 'log_only'),
            'maintenance_mode_enabled' => InputNormalizer::bool($data['maintenance_mode_enabled'] ?? $current['maintenance_mode_enabled']),
            'maintenance_message' => trim((string) ($data['maintenance_message'] ?? $current['maintenance_message'])),
        ]);

        $this->audit->record($userId, 'update', 'settings', 'system_settings', 1, $current, $updated, $request);

        return $updated;
    }

    public function setMaintenance(bool $enabled, ?string $message, int $userId, ?Request $request = null): array
    {
        $current = $this->settings();
        $updated = SystemSettings::save([
            ...$current,
            'maintenance_mode_enabled' => $enabled,
            'maintenance_message' => trim((string) ($message ?: $current['maintenance_message'])),
        ]);

        $this->audit->record($userId, 'update', 'settings', 'system_settings', 1, $current, [
            'maintenance_mode_enabled' => $updated['maintenance_mode_enabled'],
            'maintenance_message' => $updated['maintenance_message'],
        ], $request);

        return $updated;
    }

    public function readiness(): array
    {
        $checks = [
            $this->databaseCheck(),
            $this->appKeyCheck(),
            $this->appDebugCheck(),
            $this->appUrlCheck(),
            $this->sessionLifetimeCheck(),
            $this->settingsStorageCheck(),
            $this->proxyTrustCheck(),
            $this->mailCheck(),
            $this->defaultAdminCredentialCheck(),
            $this->directoryCheck('Storage directory', dirname(__DIR__, 2) . '/storage'),
            $this->directoryCheck('Log directory', dirname(__DIR__, 2) . '/storage/logs'),
            $this->directoryCheck('Session directory', dirname(__DIR__, 2) . '/storage/sessions'),
            $this->directoryCheck('Backup directory', dirname(__DIR__, 2) . '/storage/backups'),
        ];

        $summary = ['pass' => 0, 'warn' => 0, 'fail' => 0];
        foreach ($checks as $check) {
            $summary[$check['status']]++;
        }

        return [
            'overall_status' => $summary['fail'] > 0 ? 'attention' : 'ready',
            'summary' => $summary,
            'checked_at' => date(DATE_ATOM),
            'checks' => $checks,
        ];
    }

    private function databaseCheck(): array
    {
        try {
            Database::fetch('SELECT 1 AS ok');
        } catch (Throwable $exception) {
            return [
                'label' => 'Database connection',
                'status' => 'fail',
                'message' => $exception->getMessage(),
            ];
        }

        return [
            'label' => 'Database connection',
            'status' => 'pass',
            'message' => 'Database queries are succeeding.',
        ];
    }

    private function appDebugCheck(): array
    {
        $env = (string) ($_ENV['APP_ENV'] ?? 'production');
        $debug = filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOL);

        if ($env === 'production' && $debug) {
            return [
                'label' => 'Production debug mode',
                'status' => 'fail',
                'message' => 'APP_DEBUG is enabled while APP_ENV is production.',
            ];
        }

        return [
            'label' => 'Debug configuration',
            'status' => $debug ? 'warn' : 'pass',
            'message' => $debug ? 'Debug mode is enabled for this environment.' : 'Debug mode is disabled.',
        ];
    }

    private function appKeyCheck(): array
    {
        $appKey = trim((string) ($_ENV['APP_KEY'] ?? ''));

        return [
            'label' => 'Application key',
            'status' => $appKey !== '' ? 'pass' : 'warn',
            'message' => $appKey !== ''
                ? 'APP_KEY is configured.'
                : 'APP_KEY is empty. Set a unique random value before release.',
        ];
    }

    private function appUrlCheck(): array
    {
        $appUrl = trim((string) ($_ENV['APP_URL'] ?? ''));
        $host = parse_url($appUrl, PHP_URL_HOST);

        if ($appUrl === '') {
            return [
                'label' => 'Application URL',
                'status' => 'warn',
                'message' => 'APP_URL is empty. Set the canonical HTTPS URL before release.',
            ];
        }

        $isLocalHost = in_array($host, ['localhost', '127.0.0.1'], true);
        if ($isLocalHost) {
            return [
                'label' => 'Application URL',
                'status' => 'warn',
                'message' => 'APP_URL still points to localhost. Replace it with the public HTTPS URL before release.',
            ];
        }

        $isHttps = strtolower((string) parse_url($appUrl, PHP_URL_SCHEME)) === 'https';

        return [
            'label' => 'Application URL',
            'status' => $isHttps ? 'pass' : 'warn',
            'message' => $isHttps
                ? 'APP_URL uses HTTPS.'
                : 'APP_URL is not using HTTPS. Use the public HTTPS URL before release.',
        ];
    }

    private function sessionLifetimeCheck(): array
    {
        $lifetimeMinutes = (int) ($_ENV['SESSION_LIFETIME'] ?? 120);

        return [
            'label' => 'Session lifetime',
            'status' => $lifetimeMinutes <= self::RECOMMENDED_SESSION_LIFETIME_MINUTES ? 'pass' : 'warn',
            'message' => $lifetimeMinutes <= self::RECOMMENDED_SESSION_LIFETIME_MINUTES
                ? 'Session lifetime is within the recommended admin range.'
                : 'Session lifetime is ' . $lifetimeMinutes . ' minutes. Reduce it to 60 minutes or less for production admin use.',
        ];
    }

    private function settingsStorageCheck(): array
    {
        $driver = SystemSettings::storageDriver();

        return [
            'label' => 'Settings storage',
            'status' => $driver === 'database' ? 'pass' : 'warn',
            'message' => $driver === 'database'
                ? 'System settings are persisted in MySQL.'
                : 'System settings are using the legacy file fallback.',
        ];
    }

    private function proxyTrustCheck(): array
    {
        $trustedProxies = trim((string) ($_ENV['TRUSTED_PROXIES'] ?? ''));
        $forwardedHeadersPresent = $this->requestHasForwardedHeaders();

        if ($trustedProxies !== '') {
            return [
                'label' => 'Trusted proxy configuration',
                'status' => 'pass',
                'message' => 'TRUSTED_PROXIES is configured.',
            ];
        }

        return [
            'label' => 'Trusted proxy configuration',
            'status' => $forwardedHeadersPresent ? 'warn' : 'pass',
            'message' => $forwardedHeadersPresent
                ? 'Forwarded headers are present but TRUSTED_PROXIES is empty. Configure proxy IPs/CIDRs before release.'
                : 'No forwarded headers detected in this runtime. Set TRUSTED_PROXIES when deploying behind HTTPS termination or a reverse proxy.',
        ];
    }

    private function mailCheck(): array
    {
        $mode = SystemSettings::get('mail_delivery_mode', 'log_only');
        if ($mode === 'log_only') {
            return [
                'label' => 'Mail delivery',
                'status' => 'warn',
                'message' => 'Mail is in log-only mode. Password-reset URLs are written to logs instead of being delivered.',
            ];
        }

        if ($mode === 'disabled') {
            return [
                'label' => 'Mail delivery',
                'status' => 'warn',
                'message' => 'Mail delivery is disabled. Email-based recovery and notifications will not be delivered.',
            ];
        }

        $configured = (string) ($_ENV['MAIL_HOST'] ?? '') !== ''
            && (string) ($_ENV['MAIL_USERNAME'] ?? '') !== ''
            && (string) ($_ENV['MAIL_PASSWORD'] ?? '') !== ''
            && (string) ($_ENV['MAIL_FROM_ADDRESS'] ?? '') !== '';

        return [
            'label' => 'Mail delivery',
            'status' => $configured ? 'pass' : 'warn',
            'message' => $configured ? 'SMTP settings are configured.' : 'SMTP settings are incomplete. Password-reset emails may not send.',
        ];
    }

    private function defaultAdminCredentialCheck(): array
    {
        try {
            $user = Database::fetch(
                "SELECT email, password_hash FROM users WHERE email = 'admin@catarmanshelter.gov.ph' AND is_deleted = 0 LIMIT 1"
            );
        } catch (Throwable $exception) {
            return [
                'label' => 'Default admin credential',
                'status' => 'warn',
                'message' => 'Could not verify the default admin credential state: ' . $exception->getMessage(),
            ];
        }

        if ($user === false) {
            return [
                'label' => 'Default admin credential',
                'status' => 'pass',
                'message' => 'Default admin account was not found.',
            ];
        }

        $usesDefaultPassword = password_verify('ChangeMe@2025', (string) ($user['password_hash'] ?? ''));

        return [
            'label' => 'Default admin credential',
            'status' => $usesDefaultPassword ? 'warn' : 'pass',
            'message' => $usesDefaultPassword
                ? 'The seeded admin credential is still active. Rotate it before release.'
                : 'Default admin credential has been rotated.',
        ];
    }

    private function directoryCheck(string $label, string $path): array
    {
        if (!is_dir($path) && !mkdir($path, 0775, true) && !is_dir($path)) {
            return [
                'label' => $label,
                'status' => 'fail',
                'message' => 'Directory could not be created: ' . $path,
            ];
        }

        if (!is_writable($path)) {
            return [
                'label' => $label,
                'status' => 'fail',
                'message' => 'Directory is not writable: ' . $path,
            ];
        }

        return [
            'label' => $label,
            'status' => 'pass',
            'message' => 'Directory is writable.',
        ];
    }

    private function mailMode(mixed $value): string
    {
        $mode = strtolower(trim((string) $value));

        return in_array($mode, ['smtp', 'log_only', 'disabled'], true) ? $mode : 'log_only';
    }

    private function requestHasForwardedHeaders(): bool
    {
        foreach (['HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED_PROTO', 'HTTP_X_FORWARDED_HOST', 'HTTP_X_FORWARDED_PORT'] as $key) {
            $value = trim((string) ($_SERVER[$key] ?? ''));
            if ($value !== '') {
                return true;
            }
        }

        return false;
    }
}

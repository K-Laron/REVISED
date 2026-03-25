<?php

declare(strict_types=1);

namespace App\Core;

use App\Support\ProxyTrust;

class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $isSecure = ProxyTrust::isSecureRequest($_SERVER);
        $lifetimeMinutes = (int) ($_ENV['SESSION_LIFETIME'] ?? 120);

        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_trans_sid', '0');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.cookie_secure', $isSecure ? '1' : '0');

        session_name($_ENV['SESSION_NAME'] ?? 'catarman_shelter_session');
        session_set_cookie_params([
            'lifetime' => $lifetimeMinutes * 60,
            'path' => '/',
            'domain' => '',
            'secure' => $isSecure,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);

        $savePath = dirname(__DIR__, 2) . '/storage/sessions';
        if (!is_dir($savePath)) {
            mkdir($savePath, 0775, true);
        }

        session_save_path($savePath);
        session_start();
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function regenerate(): bool
    {
        return session_regenerate_id(true);
    }

    public static function destroy(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            return;
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Strict',
            ]);
        }

        session_destroy();
    }

    public static function clearAuthState(): void
    {
        self::forget('auth.user');
        self::forget('auth.session_token');
    }
}

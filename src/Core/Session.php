<?php

declare(strict_types=1);

namespace App\Core;

use App\Support\ProxyTrust;

class Session
{
    private array $config;

    public function __construct(?array $config = null)
    {
        $this->config = $config ?? [
            'lifetime' => (int) ($_ENV['SESSION_LIFETIME'] ?? 120),
            'name' => $_ENV['SESSION_NAME'] ?? 'catarman_shelter_session',
            'save_path' => dirname(__DIR__, 2) . '/storage/sessions',
        ];
    }

    public function instanceStart(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $isSecure = ProxyTrust::isSecureRequest($_SERVER);
        $lifetimeMinutes = $this->config['lifetime'];

        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_trans_sid', '0');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.cookie_secure', $isSecure ? '1' : '0');

        session_name($this->config['name']);
        session_set_cookie_params([
            'lifetime' => $lifetimeMinutes * 60,
            'path' => '/',
            'domain' => '',
            'secure' => $isSecure,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);

        if (!is_dir($this->config['save_path'])) {
            mkdir($this->config['save_path'], 0775, true);
        }

        session_save_path($this->config['save_path']);
        session_start();
    }

    /*
    |--------------------------------------------------------------------------
    | Static Bridge
    |--------------------------------------------------------------------------
    */

    private static function getInstance(): self
    {
        return App::container()->get(self::class);
    }

    public static function start(): void
    {
        self::getInstance()->instanceStart();
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::getInstance()->instanceGet($key, $default);
    }

    public static function put(string $key, mixed $value): void
    {
        self::getInstance()->instancePut($key, $value);
    }

    public static function forget(string $key): void
    {
        self::getInstance()->instanceForget($key);
    }

    public static function regenerate(): bool
    {
        return self::getInstance()->instanceRegenerate();
    }

    public static function destroy(): void
    {
        self::getInstance()->instanceDestroy();
    }

    public function instanceGet(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function instancePut(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function instanceForget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function instanceRegenerate(): bool
    {
        return session_regenerate_id(true);
    }

    public function instanceDestroy(): void
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
                'httponly' => true,
                'samesite' => $params['samesite'] ?? 'Strict',
            ]);
        }

        session_destroy();
    }

    public function clearAuthState(): void
    {
        $this->instanceForget('auth.user');
        $this->instanceForget('auth.session_token');
    }
}

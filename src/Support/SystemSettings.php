<?php

declare(strict_types=1);

namespace App\Support;

use App\Core\App;
use App\Core\Database;
use RuntimeException;
use Throwable;

class SystemSettings
{
    private ?array $cache = null;
    private ?bool $databaseStoreAvailable = null;

    public function __construct(private readonly Database $db)
    {
    }

    public function bootstrap(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $settings = $this->defaults();
        $runtimeSettings = $this->readFromCache();
        if ($runtimeSettings !== []) {
            $this->cache = array_replace($settings, $runtimeSettings);

            return $this->cache;
        }

        $legacySettings = $this->readFromFile();
        if ($legacySettings !== []) {
            $this->cache = array_replace($settings, $legacySettings);

            return $this->cache;
        }

        $this->cache = $settings;

        return $this->cache;
    }

    public function all(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $settings = $this->bootstrap();

        try {
            if ($this->databaseStoreAvailable()) {
                $databaseSettings = $this->readFromDatabase();
                if ($databaseSettings !== []) {
                    $this->cache = array_replace($settings, $databaseSettings);
                    $this->writeToCache($this->cache);

                    return $this->cache;
                }
            }
        } catch (Throwable) {
            $this->databaseStoreAvailable = false;
        }

        return $this->cache;
    }

    public function instanceGet(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::instance()->instanceGet($key, $default);
    }

    public function save(array $settings): array
    {
        $merged = array_replace($this->defaults(), $settings);

        if ($this->databaseStoreAvailable()) {
            $this->writeToDatabase($merged);
        } else {
            $this->writeToFile($merged);
        }

        $this->writeToCache($merged);

        $this->cache = $merged;

        return $merged;
    }

    public function migrateLegacyFileToDatabase(): bool
    {
        if (!$this->databaseStoreAvailable()) {
            return false;
        }

        $legacySettings = $this->readFromFile();
        if ($legacySettings === []) {
            return false;
        }

        $merged = array_replace($this->defaults(), $legacySettings);
        $this->writeToDatabase($merged);
        $this->writeToCache($merged);
        $this->cache = $merged;

        return true;
    }

    public function storageDriver(): string
    {
        return $this->databaseStoreAvailable() ? 'database' : 'file';
    }

    public function path(): string
    {
        return dirname(__DIR__, 2) . '/storage/config/system_settings.json';
    }

    public function cachePath(): string
    {
        return dirname(__DIR__, 2) . '/storage/cache/system_settings.json';
    }

    public function defaults(): array
    {
        return [
            'app_name' => $_ENV['APP_NAME'] ?? 'Catarman Animal Shelter',
            'organization_name' => 'Catarman Dog Pound',
            'public_portal_enabled' => true,
            'contact_email' => $_ENV['MAIL_FROM_ADDRESS'] ?? '',
            'contact_phone' => '',
            'office_address' => '',
            'mail_delivery_mode' => 'log_only',
            'maintenance_mode_enabled' => false,
            'maintenance_message' => 'The system is temporarily unavailable while maintenance is in progress.',
        ];
    }

    private function databaseStoreAvailable(): bool
    {
        if ($this->databaseStoreAvailable !== null) {
            return $this->databaseStoreAvailable;
        }

        try {
            $row = $this->db->fetch("SHOW TABLES LIKE 'system_settings'");
            $this->databaseStoreAvailable = $row !== false;
        } catch (Throwable) {
            $this->databaseStoreAvailable = false;
        }

        return $this->databaseStoreAvailable;
    }

    private function readFromDatabase(): array
    {
        $rows = $this->db->fetchAll('SELECT setting_key, setting_value FROM system_settings');
        $settings = [];

        foreach ($rows as $row) {
            $key = (string) ($row['setting_key'] ?? '');
            if ($key === '') {
                continue;
            }

            $decoded = json_decode((string) ($row['setting_value'] ?? 'null'), true);
            $settings[$key] = $decoded;
        }

        return $settings;
    }

    private function writeToDatabase(array $settings): void
    {
        foreach ($settings as $key => $value) {
            $encoded = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($encoded === false) {
                throw new RuntimeException('Failed to encode setting [' . $key . '].');
            }

            $this->db->execute(
                'INSERT INTO system_settings (setting_key, setting_value)
                 VALUES (:setting_key, CAST(:setting_value AS JSON))
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = CURRENT_TIMESTAMP',
                [
                    'setting_key' => $key,
                    'setting_value' => $encoded,
                ]
            );
        }
    }

    private function readFromFile(): array
    {
        return $this->readJsonFile($this->path());
    }

    private function readFromCache(): array
    {
        return $this->readJsonFile($this->cachePath());
    }

    private function writeToFile(array $settings): void
    {
        $this->writeJsonFile($this->path(), $settings);
    }

    private function writeToCache(array $settings): void
    {
        $this->writeJsonFile($this->cachePath(), $settings);
    }

    private function readJsonFile(string $path): array
    {
        if (!is_file($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? $decoded : [];
    }

    private function writeJsonFile(string $path, array $settings): void
    {
        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('Failed to create the settings directory.');
        }

        $encoded = json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($encoded === false || file_put_contents($path, $encoded) === false) {
            throw new RuntimeException('Failed to persist system settings.');
        }
    }

    /**
     * Static bridge for legacy support.
     * @deprecated Use DI to inject SystemSettings instance.
     */
    public static function instance(): self
    {
        return App::make(self::class);
    }

    /** @deprecated */
    public static function getStatic(string $key, mixed $default = null): mixed
    {
        return self::instance()->instanceGet($key, $default);
    }

    /**
     * @deprecated Proxy for legacy code calling SystemSettings::get()
     */
    public static function __callStatic(string $name, array $arguments)
    {
        $instance = self::instance();
        if (method_exists($instance, $name)) {
            return $instance->$name(...$arguments);
        }

        throw new RuntimeException("Method $name does not exist on SystemSettings.");
    }
}

<?php

declare(strict_types=1);

namespace App\Support;

use App\Core\Database;
use RuntimeException;
use Throwable;

class SystemSettings
{
    private static ?array $cache = null;
    private static ?bool $databaseStoreAvailable = null;

    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $settings = self::defaults();

        try {
            if (self::databaseStoreAvailable()) {
                $databaseSettings = self::readFromDatabase();
                if ($databaseSettings !== []) {
                    self::$cache = array_replace($settings, $databaseSettings);

                    return self::$cache;
                }
            }
        } catch (Throwable) {
            self::$databaseStoreAvailable = false;
        }

        $legacySettings = self::readFromFile();
        if ($legacySettings !== []) {
            $settings = array_replace($settings, $legacySettings);
        }

        self::$cache = $settings;

        return self::$cache;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::all()[$key] ?? $default;
    }

    public static function save(array $settings): array
    {
        $merged = array_replace(self::defaults(), $settings);

        if (self::databaseStoreAvailable()) {
            self::writeToDatabase($merged);
            self::$cache = $merged;

            return $merged;
        }

        self::writeToFile($merged);

        self::$cache = $merged;

        return $merged;
    }

    public static function migrateLegacyFileToDatabase(): bool
    {
        if (!self::databaseStoreAvailable()) {
            return false;
        }

        $legacySettings = self::readFromFile();
        if ($legacySettings === []) {
            return false;
        }

        $merged = array_replace(self::defaults(), $legacySettings);
        self::writeToDatabase($merged);
        self::$cache = $merged;

        return true;
    }

    public static function storageDriver(): string
    {
        return self::databaseStoreAvailable() ? 'database' : 'file';
    }

    public static function path(): string
    {
        return dirname(__DIR__, 2) . '/storage/config/system_settings.json';
    }

    public static function defaults(): array
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

    private static function databaseStoreAvailable(): bool
    {
        if (self::$databaseStoreAvailable !== null) {
            return self::$databaseStoreAvailable;
        }

        try {
            $row = Database::fetch("SHOW TABLES LIKE 'system_settings'");
            self::$databaseStoreAvailable = $row !== false;
        } catch (Throwable) {
            self::$databaseStoreAvailable = false;
        }

        return self::$databaseStoreAvailable;
    }

    private static function readFromDatabase(): array
    {
        $rows = Database::fetchAll('SELECT setting_key, setting_value FROM system_settings');
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

    private static function writeToDatabase(array $settings): void
    {
        foreach ($settings as $key => $value) {
            $encoded = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($encoded === false) {
                throw new RuntimeException('Failed to encode setting [' . $key . '].');
            }

            Database::execute(
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

    private static function readFromFile(): array
    {
        $path = self::path();
        if (!is_file($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? $decoded : [];
    }

    private static function writeToFile(array $settings): void
    {
        $directory = dirname(self::path());
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('Failed to create the settings directory.');
        }

        $encoded = json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($encoded === false || file_put_contents(self::path(), $encoded) === false) {
            throw new RuntimeException('Failed to persist system settings.');
        }
    }
}

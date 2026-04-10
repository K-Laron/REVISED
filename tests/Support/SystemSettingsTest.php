<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Core\Database;
use App\Support\SystemSettings;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class SystemSettingsTest extends TestCase
{
    private ?string $originalSettingsFile = null;
    private ?string $originalCacheFile = null;
    private SystemSettings $settings;

    protected function setUp(): void
    {
        parent::setUp();

        $db = $this->createMock(Database::class);
        $this->settings = new SystemSettings($db);

        $this->originalSettingsFile = $this->readFileIfPresent($this->settings->path());
        $this->originalCacheFile = $this->readFileIfPresent($this->settings->cachePath());

        $this->deleteIfPresent($this->settings->path());
        $this->deleteIfPresent($this->settings->cachePath());
        $this->resetSystemSettingsState();
        $_ENV['APP_NAME'] = 'Env Shelter';
        $_ENV['MAIL_FROM_ADDRESS'] = 'env@example.test';
    }

    protected function tearDown(): void
    {
        $this->restoreFile($this->settings->path(), $this->originalSettingsFile);
        $this->restoreFile($this->settings->cachePath(), $this->originalCacheFile);
        $this->resetSystemSettingsState();

        parent::tearDown();
    }

    public function testBootstrapReadsRuntimeCacheWithoutTouchingDatabaseDetection(): void
    {
        $this->writeJson($this->settings->cachePath(), [
            'app_name' => 'Cached Shelter',
            'maintenance_mode_enabled' => true,
        ]);

        $result = $this->settings->bootstrap();

        self::assertSame('Cached Shelter', $result['app_name']);
        self::assertTrue($result['maintenance_mode_enabled']);
        self::assertSame('env@example.test', $result['contact_email']);
        self::assertNull($this->systemSettingsProperty('databaseStoreAvailable'));
    }

    public function testAllFallsBackToBootstrapCacheWhenDatabaseStoreIsUnavailable(): void
    {
        $this->writeJson($this->settings->cachePath(), [
            'app_name' => 'Cached Shelter',
        ]);
        $this->setSystemSettingsProperty('databaseStoreAvailable', false);

        $result = $this->settings->all();

        self::assertSame('Cached Shelter', $result['app_name']);
        self::assertSame('env@example.test', $result['contact_email']);
    }

    public function testSaveRefreshesRuntimeCacheWhenUsingLegacyFileStorage(): void
    {
        $this->setSystemSettingsProperty('databaseStoreAvailable', false);

        $saved = $this->settings->save([
            'app_name' => 'Saved Shelter',
            'maintenance_mode_enabled' => true,
        ]);

        self::assertSame('Saved Shelter', $saved['app_name']);
        self::assertTrue($saved['maintenance_mode_enabled']);

        $cacheFile = json_decode((string) file_get_contents($this->settings->cachePath()), true, 512, JSON_THROW_ON_ERROR);
        $settingsFile = json_decode((string) file_get_contents($this->settings->path()), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('Saved Shelter', $cacheFile['app_name']);
        self::assertSame('Saved Shelter', $settingsFile['app_name']);

        $this->setSystemSettingsProperty('cache', null);

        self::assertSame('Saved Shelter', $this->settings->bootstrap()['app_name']);
    }

    private function resetSystemSettingsState(): void
    {
        $this->setSystemSettingsProperty('cache', null);
        $this->setSystemSettingsProperty('databaseStoreAvailable', null);
    }

    private function systemSettingsProperty(string $property): mixed
    {
        $reflection = new ReflectionClass($this->settings);
        $propertyReflection = $reflection->getProperty($property);
        $propertyReflection->setAccessible(true);

        return $propertyReflection->getValue($this->settings);
    }

    private function setSystemSettingsProperty(string $property, mixed $value): void
    {
        $reflection = new ReflectionClass($this->settings);
        $propertyReflection = $reflection->getProperty($property);
        $propertyReflection->setAccessible(true);
        $propertyReflection->setValue($this->settings, $value);
    }

    private function readFileIfPresent(string $path): ?string
    {
        return is_file($path) ? (string) file_get_contents($path) : null;
    }

    private function restoreFile(string $path, ?string $contents): void
    {
        if ($contents === null) {
            $this->deleteIfPresent($path);

            return;
        }

        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        file_put_contents($path, $contents);
    }

    private function deleteIfPresent(string $path): void
    {
        if (is_file($path)) {
            unlink($path);
        }
    }

    private function writeJson(string $path, array $payload): void
    {
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        file_put_contents($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}

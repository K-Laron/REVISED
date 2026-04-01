<?php

declare(strict_types=1);

namespace Tests\Middleware\Support;

use PHPUnit\Framework\TestCase;

final class FileRateLimitStoreTest extends TestCase
{
    private string $cacheDirectory;
    private string $cachePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'rate-limit-' . bin2hex(random_bytes(5));
        $this->cachePath = $this->cacheDirectory . DIRECTORY_SEPARATOR . 'rate_limits.json';
    }

    protected function tearDown(): void
    {
        if (is_file($this->cachePath)) {
            @unlink($this->cachePath);
        }

        if (is_dir($this->cacheDirectory)) {
            @rmdir($this->cacheDirectory);
        }

        parent::tearDown();
    }

    public function testHitCreatesMissingDirectoryAndAllowsRequestsUntilTheLimitIsReached(): void
    {
        $class = 'App\\Middleware\\Support\\FileRateLimitStore';
        if (!class_exists($class)) {
            self::fail('Expected App\\Middleware\\Support\\FileRateLimitStore to exist.');
        }

        $store = new $class($this->cachePath);

        self::assertFalse($store->hit('ip:/login', 2, 60, 100));
        self::assertFalse($store->hit('ip:/login', 2, 60, 100));
        self::assertTrue($store->hit('ip:/login', 2, 60, 100));
        self::assertFileExists($this->cachePath);
    }

    public function testHitPrunesExpiredEntriesBeforeWritingTheUpdatedCache(): void
    {
        $class = 'App\\Middleware\\Support\\FileRateLimitStore';
        if (!class_exists($class)) {
            self::fail('Expected App\\Middleware\\Support\\FileRateLimitStore to exist.');
        }

        mkdir($this->cacheDirectory, 0775, true);
        file_put_contents($this->cachePath, json_encode([
            'expired-key' => ['attempts' => 4, 'expires_at' => 50],
        ], JSON_PRETTY_PRINT));

        $store = new $class($this->cachePath);
        self::assertFalse($store->hit('fresh-key', 3, 60, 100));

        $decoded = json_decode((string) file_get_contents($this->cachePath), true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayNotHasKey('expired-key', $decoded);
        self::assertSame(1, $decoded['fresh-key']['attempts']);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Support\Cache;

use App\Support\Cache\FileCacheStore;
use PHPUnit\Framework\TestCase;

final class FileCacheStoreTest extends TestCase
{
    private string $cacheDirectory;
    private string $cachePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'dashboard-cache-' . bin2hex(random_bytes(5));
        $this->cachePath = $this->cacheDirectory . DIRECTORY_SEPARATOR . 'app_cache.json';
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

    public function testRememberReturnsCachedPayloadUntilTheTtlExpires(): void
    {
        $store = new FileCacheStore($this->cachePath);
        $calls = 0;

        $first = $store->remember('dashboard.bootstrap', 30, static function () use (&$calls): array {
            $calls++;

            return ['count' => 1];
        });
        $second = $store->remember('dashboard.bootstrap', 30, static function () use (&$calls): array {
            $calls++;

            return ['count' => 2];
        });

        self::assertSame(['count' => 1], $first);
        self::assertSame(['count' => 1], $second);
        self::assertSame(1, $calls);
    }
}

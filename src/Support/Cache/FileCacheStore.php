<?php

declare(strict_types=1);

namespace App\Support\Cache;

use RuntimeException;

final class FileCacheStore
{
    private ?array $loadedCache = null;

    private array $dirtyEntries = [];

    public function __construct(
        private readonly string $path = __DIR__ . '/../../../storage/cache/app_cache.json'
    ) {
    }

    public function remember(string $key, int $ttlSeconds, callable $resolver): mixed
    {
        $cache = $this->loadCache();
        $entry = $cache[$key] ?? null;

        if (is_array($entry) && (int) ($entry['expires_at'] ?? 0) >= time()) {
            return $entry['value'] ?? null;
        }

        $value = $resolver();
        $entry = [
            'expires_at' => time() + $ttlSeconds,
            'value' => $value,
        ];

        $this->loadedCache[$key] = $entry;
        $this->dirtyEntries[$key] = $entry;
        $this->persistDirtyEntries();

        return $value;
    }

    private function loadCache(): array
    {
        if ($this->loadedCache !== null) {
            return $this->loadedCache;
        }

        $handle = $this->openHandle();

        try {
            $this->loadedCache = $this->readCache($handle);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }

        return $this->loadedCache;
    }

    private function persistDirtyEntries(): void
    {
        if ($this->dirtyEntries === []) {
            return;
        }

        $handle = $this->openHandle();

        try {
            $cache = $this->readCache($handle);
            foreach ($this->dirtyEntries as $key => $entry) {
                $cache[$key] = $entry;
            }

            $this->writeCache($handle, $cache);
            $this->loadedCache = $cache;
            $this->dirtyEntries = [];
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    private function openHandle()
    {
        $directory = dirname($this->path);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('Failed to create the app cache directory.');
        }

        $handle = fopen($this->path, 'c+');
        if ($handle === false) {
            throw new RuntimeException('Failed to open the app cache file.');
        }

        if (!flock($handle, LOCK_EX)) {
            fclose($handle);
            throw new RuntimeException('Failed to lock the app cache file.');
        }

        return $handle;
    }

    private function readCache($handle): array
    {
        rewind($handle);
        $contents = stream_get_contents($handle);
        if ($contents === false || $contents === '') {
            return [];
        }

        $decoded = json_decode($contents, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function writeCache($handle, array $cache): void
    {
        $encoded = json_encode($cache, JSON_PRETTY_PRINT);
        if ($encoded === false) {
            throw new RuntimeException('Failed to encode the app cache.');
        }

        rewind($handle);
        ftruncate($handle, 0);
        if (fwrite($handle, $encoded) === false) {
            throw new RuntimeException('Failed to persist the app cache.');
        }

        fflush($handle);
    }
}

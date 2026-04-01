<?php

declare(strict_types=1);

namespace App\Middleware\Support;

use RuntimeException;

final class FileRateLimitStore
{
    public function __construct(
        private readonly string $path = __DIR__ . '/../../../storage/cache/rate_limits.json'
    ) {
    }

    public function hit(string $key, int $maxAttempts, int $windowSeconds, int $now): bool
    {
        $handle = $this->openHandle();

        try {
            $cache = $this->readCache($handle);
            $cache = array_filter(
                $cache,
                static fn (mixed $entry): bool => is_array($entry) && (int) ($entry['expires_at'] ?? 0) > $now
            );

            $entry = $cache[$key] ?? ['attempts' => 0, 'expires_at' => $now + $windowSeconds];
            if ((int) ($entry['expires_at'] ?? 0) <= $now) {
                $entry = ['attempts' => 0, 'expires_at' => $now + $windowSeconds];
            }

            if ((int) ($entry['attempts'] ?? 0) >= $maxAttempts) {
                $cache[$key] = $entry;
                $this->writeCache($handle, $cache);

                return true;
            }

            $entry['attempts'] = (int) ($entry['attempts'] ?? 0) + 1;
            $cache[$key] = $entry;
            $this->writeCache($handle, $cache);

            return false;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    private function openHandle()
    {
        $directory = dirname($this->path);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('Failed to create the rate limit cache directory.');
        }

        $handle = fopen($this->path, 'c+');
        if ($handle === false) {
            throw new RuntimeException('Failed to open the rate limit cache file.');
        }

        if (!flock($handle, LOCK_EX)) {
            fclose($handle);
            throw new RuntimeException('Failed to lock the rate limit cache file.');
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
            throw new RuntimeException('Failed to encode the rate limit cache.');
        }

        rewind($handle);
        ftruncate($handle, 0);
        if (fwrite($handle, $encoded) === false) {
            throw new RuntimeException('Failed to persist the rate limit cache.');
        }

        fflush($handle);
    }
}

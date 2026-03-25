<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use Closure;
use Throwable;

class RateLimitMiddleware
{
    public function handle(Request $request, Closure $next, ?string $parameter = null): mixed
    {
        $maxAttempts = max(1, (int) ($parameter ?? 60));
        $windowSeconds = 60;
        $key = sha1($request->ip() . ':' . $request->path());
        $now = time();
        $windowStart = date('Y-m-d H:i:s', $now);
        $expiresAt = date('Y-m-d H:i:s', $now + $windowSeconds);

        try {
            Database::execute('DELETE FROM rate_limit_attempts WHERE expires_at <= NOW()');
            $row = Database::fetch('SELECT * FROM rate_limit_attempts WHERE `key` = :key', ['key' => $key]);

            if ($row === false) {
                Database::execute(
                    'INSERT INTO rate_limit_attempts (`key`, attempts, window_start, expires_at) VALUES (:key, 1, :window_start, :expires_at)',
                    ['key' => $key, 'window_start' => $windowStart, 'expires_at' => $expiresAt]
                );

                return $next($request);
            }

            if (strtotime((string) $row['expires_at']) <= $now) {
                Database::execute(
                    'UPDATE rate_limit_attempts SET attempts = 1, window_start = :window_start, expires_at = :expires_at WHERE `key` = :key',
                    ['key' => $key, 'window_start' => $windowStart, 'expires_at' => $expiresAt]
                );

                return $next($request);
            }

            if ((int) $row['attempts'] >= $maxAttempts) {
                return Response::error(429, 'RATE_LIMITED', 'Too many requests. Please try again later.');
            }

            Database::execute(
                'UPDATE rate_limit_attempts SET attempts = attempts + 1 WHERE `key` = :key',
                ['key' => $key]
            );
        } catch (Throwable) {
            $file = dirname(__DIR__, 2) . '/storage/cache/rate_limits.json';
            $cache = is_file($file) ? json_decode((string) file_get_contents($file), true) : [];
            $entry = $cache[$key] ?? ['attempts' => 0, 'expires_at' => $now + $windowSeconds];

            if (($entry['expires_at'] ?? 0) <= $now) {
                $entry = ['attempts' => 0, 'expires_at' => $now + $windowSeconds];
            }

            if (($entry['attempts'] ?? 0) >= $maxAttempts) {
                return Response::error(429, 'RATE_LIMITED', 'Too many requests. Please try again later.');
            }

            $entry['attempts']++;
            $cache[$key] = $entry;
            file_put_contents($file, json_encode($cache, JSON_PRETTY_PRINT));
        }

        return $next($request);
    }
}

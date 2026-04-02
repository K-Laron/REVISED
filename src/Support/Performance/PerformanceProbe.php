<?php

declare(strict_types=1);

namespace App\Support\Performance;

final class PerformanceProbe
{
    private static bool $forceSet = false;
    private static bool $forcedEnabled = false;
    private static bool $enabled = false;
    private static ?float $startedAt = null;
    private static array $summary = [];

    public static function forceEnabled(bool $enabled): void
    {
        self::$forceSet = true;
        self::$forcedEnabled = $enabled;
    }

    public static function startRequest(string $method, string $path): void
    {
        self::$enabled = self::$forceSet
            ? self::$forcedEnabled
            : filter_var($_ENV['APP_PERFORMANCE_DEBUG'] ?? ($_ENV['APP_DEBUG'] ?? false), FILTER_VALIDATE_BOOL);
        self::$startedAt = microtime(true);
        self::$summary = [
            'method' => $method,
            'path' => $path,
            'query_count' => 0,
            'database_time_ms' => 0.0,
            'request_time_ms' => 0.0,
            'slow_queries' => [],
        ];
    }

    public static function recordQuery(string $sql, float $durationMs): void
    {
        if (!self::$enabled) {
            return;
        }

        self::$summary['query_count']++;
        self::$summary['database_time_ms'] += $durationMs;
        self::$summary['slow_queries'][] = [
            'sql' => $sql,
            'duration_ms' => round($durationMs, 2),
        ];

        usort(
            self::$summary['slow_queries'],
            static fn (array $left, array $right): int => $right['duration_ms'] <=> $left['duration_ms']
        );
        self::$summary['slow_queries'] = array_slice(self::$summary['slow_queries'], 0, 5);
    }

    public static function finishRequest(): array
    {
        if (!self::$enabled || self::$startedAt === null) {
            return [];
        }

        self::$summary['database_time_ms'] = round((float) self::$summary['database_time_ms'], 2);
        self::$summary['request_time_ms'] = round((microtime(true) - self::$startedAt) * 1000, 2);

        return self::$summary;
    }

    public static function headersFromSummary(array $summary): array
    {
        if ($summary === []) {
            return [];
        }

        return [
            'X-App-Request-Time-Ms' => number_format((float) $summary['request_time_ms'], 2, '.', ''),
            'X-App-Query-Count' => (string) $summary['query_count'],
            'X-App-Database-Time-Ms' => number_format((float) $summary['database_time_ms'], 2, '.', ''),
        ];
    }

    public static function reset(): void
    {
        self::$forceSet = false;
        self::$forcedEnabled = false;
        self::$enabled = false;
        self::$startedAt = null;
        self::$summary = [];
    }
}

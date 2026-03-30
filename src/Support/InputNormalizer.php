<?php

declare(strict_types=1);

namespace App\Support;

final class InputNormalizer
{
    public static function nullIfBlank(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    public static function bool(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? false;
    }

    public static function dateTime(mixed $value): ?string
    {
        $normalized = self::nullIfBlank($value);
        if ($normalized === null) {
            return null;
        }

        $normalized = str_replace('T', ' ', $normalized);

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $normalized) === 1) {
            return $normalized . ' 00:00:00';
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $normalized) === 1) {
            return $normalized . ':00';
        }

        return $normalized;
    }

    public static function date(mixed $value, bool $strict = false): ?string
    {
        $normalized = self::nullIfBlank($value);
        if ($normalized === null) {
            return null;
        }

        if ($strict) {
            return preg_match('/^\d{4}-\d{2}-\d{2}$/', $normalized) === 1 ? $normalized : null;
        }

        return substr(str_replace('T', ' ', $normalized), 0, 10);
    }

    public static function intOrNull(mixed $value): ?int
    {
        $normalized = self::nullIfBlank($value);

        return $normalized === null ? null : (int) $normalized;
    }

    public static function decimalOrNull(mixed $value, int $precision = 2): ?float
    {
        $normalized = self::nullIfBlank($value);

        return $normalized === null ? null : round((float) $normalized, $precision);
    }

    public static function normalizeDateTimeFields(array $payload, array $fields): array
    {
        foreach ($fields as $field) {
            $normalized = self::dateTime($payload[$field] ?? null);
            if ($normalized !== null) {
                $payload[$field] = $normalized;
            }
        }

        return $payload;
    }

    public static function normalizeDateFields(array $payload, array $fields, bool $strict = false): array
    {
        foreach ($fields as $field) {
            $normalized = self::date($payload[$field] ?? null, $strict);
            if ($normalized !== null) {
                $payload[$field] = $normalized;
            }
        }

        return $payload;
    }

    public static function daysSince(mixed $value, ?int $nowTimestamp = null): int
    {
        $timestamp = strtotime((string) $value);
        if ($timestamp === false) {
            return 0;
        }

        $now = $nowTimestamp ?? time();

        return max(0, (int) floor(($now - $timestamp) / 86400));
    }
}

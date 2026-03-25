<?php

declare(strict_types=1);

namespace App\Helpers;

class Sanitizer
{
    public static function clean(string $input): string
    {
        $input = trim($input);
        $input = strip_tags($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');

        return $input;
    }

    public static function cleanArray(array $input): array
    {
        $clean = [];

        foreach ($input as $key => $value) {
            if (is_array($value)) {
                $clean[$key] = self::cleanArray($value);
                continue;
            }

            $clean[$key] = is_string($value) ? self::clean($value) : $value;
        }

        return $clean;
    }

    public static function email(?string $input): ?string
    {
        if ($input === null || $input === '') {
            return $input;
        }

        return strtolower((string) filter_var($input, FILTER_SANITIZE_EMAIL));
    }

    public static function phone(?string $input): ?string
    {
        if ($input === null || $input === '') {
            return $input;
        }

        return preg_replace('/(?!^\+)[^\d]/', '', $input);
    }

    public static function username(?string $input): ?string
    {
        if ($input === null || trim($input) === '') {
            return null;
        }

        $value = strtolower(trim($input));
        if (!preg_match('/^[a-z0-9_-]+$/', $value)) {
            return null;
        }

        return $value;
    }

    public static function currency(mixed $value): float
    {
        return round((float) $value, 2);
    }

    public static function integerId(mixed $value): int
    {
        return (int) $value;
    }

    public static function fileName(string $value): string
    {
        return str_replace(['..', '/', '\\'], '', $value);
    }

    public static function escapeLike(string $value): string
    {
        return str_replace(['%', '_'], ['\%', '\_'], $value);
    }
}

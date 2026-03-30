<?php

declare(strict_types=1);

namespace App\Support;

final class Pagination
{
    public static function page(mixed $value): int
    {
        return max(1, (int) ($value ?? 1));
    }

    public static function perPage(mixed $value, int $default, int $max = 100): int
    {
        $perPage = $value === null ? $default : (int) $value;

        return max(1, min($max, $perPage));
    }

    public static function meta(int $page, int $perPage, int $total): array
    {
        return [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => (int) ceil(max(1, $total) / $perPage),
        ];
    }
}

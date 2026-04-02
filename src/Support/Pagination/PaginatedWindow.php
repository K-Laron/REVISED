<?php

declare(strict_types=1);

namespace App\Support\Pagination;

final class PaginatedWindow
{
    public static function resolve(array $rows, int $page, int $perPage, callable $counter): array
    {
        $overflow = count($rows) > $perPage;
        $items = $overflow ? array_slice($rows, 0, $perPage) : $rows;

        return [
            'items' => $items,
            'total' => $overflow
                ? (int) $counter()
                : (($page - 1) * $perPage) + count($items),
        ];
    }
}

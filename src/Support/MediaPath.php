<?php

declare(strict_types=1);

namespace App\Support;

class MediaPath
{
    public static function normalizePublicImagePath(?string $path): ?string
    {
        $relativePath = ltrim(trim((string) $path), "/\\");
        if ($relativePath === '') {
            return null;
        }

        $absolutePath = dirname(__DIR__, 2) . '/public/' . str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $relativePath);
        if (!is_file($absolutePath)) {
            return null;
        }

        if (@getimagesize($absolutePath) === false) {
            return null;
        }

        return str_replace('\\', '/', $relativePath);
    }

    public static function filterValidImageRows(array $rows, string $pathKey = 'file_path'): array
    {
        $normalized = [];

        foreach ($rows as $row) {
            $row[$pathKey] = self::normalizePublicImagePath($row[$pathKey] ?? null);
            if ($row[$pathKey] === null) {
                continue;
            }

            $normalized[] = $row;
        }

        return array_values($normalized);
    }
}

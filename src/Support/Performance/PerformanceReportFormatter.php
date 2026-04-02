<?php

declare(strict_types=1);

namespace App\Support\Performance;

final class PerformanceReportFormatter
{
    public static function markdown(array $records, string $heading): string
    {
        $lines = [
            '## ' . $heading,
            '',
            '| Label | Status | Request Time (ms) | Query Count | Database Time (ms) |',
            '| --- | --- | --- | --- | --- |',
        ];

        foreach ($records as $record) {
            $headers = $record['headers'] ?? [];
            $lines[] = sprintf(
                '| %s | %s | %s | %s | %s |',
                $record['label'] ?? 'unknown',
                $record['status'] ?? 0,
                $headers['X-App-Request-Time-Ms'] ?? '0.00',
                $headers['X-App-Query-Count'] ?? '0',
                $headers['X-App-Database-Time-Ms'] ?? '0.00'
            );
        }

        return implode(PHP_EOL, $lines) . PHP_EOL;
    }
}

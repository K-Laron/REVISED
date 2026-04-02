<?php

declare(strict_types=1);

namespace Tests\Support\Performance;

use App\Support\Performance\PerformanceReportFormatter;
use PHPUnit\Framework\TestCase;

final class PerformanceReportFormatterTest extends TestCase
{
    public function testMarkdownTableIncludesTheMeasuredHeaders(): void
    {
        $markdown = PerformanceReportFormatter::markdown([
            [
                'label' => 'dashboard_bootstrap',
                'status' => 200,
                'headers' => [
                    'X-App-Request-Time-Ms' => '18.40',
                    'X-App-Query-Count' => '6',
                    'X-App-Database-Time-Ms' => '12.15',
                ],
            ],
        ], 'Before Optimization');

        self::assertStringContainsString('| dashboard_bootstrap | 200 | 18.40 | 6 | 12.15 |', $markdown);
    }
}

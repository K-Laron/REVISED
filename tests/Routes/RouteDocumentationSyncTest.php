<?php

declare(strict_types=1);

namespace Tests\Routes;

use PHPUnit\Framework\TestCase;

final class RouteDocumentationSyncTest extends TestCase
{
    public function testReadmeMentionsCurrentPublishedSurfaceAndReleaseGate(): void
    {
        $readme = file_get_contents(dirname(__DIR__, 2) . '/README.md');

        self::assertIsString($readme);
        self::assertStringContainsString('`34` web routes', $readme);
        self::assertStringContainsString('`126` production API routes', $readme);
        self::assertStringContainsString('`39` tables', $readme);
        self::assertStringContainsString('scripts/run-release-checks.ps1', $readme);
    }
}

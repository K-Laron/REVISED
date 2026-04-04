<?php

declare(strict_types=1);

namespace Tests\Integration\Support;

use PHPUnit\Framework\TestCase;

final class DatabaseFixtureFactoryAdoptionTest extends TestCase
{
    public function testDatabaseIntegrationTestCaseDelegatesFixtureCreationToFactory(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 3) . '/tests/Integration/DatabaseIntegrationTestCase.php');

        self::assertStringContainsString('DatabaseFixtureFactory', $source);
    }
}

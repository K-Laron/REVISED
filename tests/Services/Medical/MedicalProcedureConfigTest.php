<?php

declare(strict_types=1);

namespace Tests\Services\Medical;

use App\Services\Medical\MedicalProcedureConfig;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class MedicalProcedureConfigTest extends TestCase
{
    public function testForTypeReturnsExpectedProcedureConfig(): void
    {
        $config = (new MedicalProcedureConfig())->forType('vaccination');

        self::assertSame('Vaccination', $config['label']);
        self::assertSame('/api/medical/vaccination', $config['endpoint']);
        self::assertSame(365, $config['default_due_days']);
        self::assertContains('vaccine_name', $config['fields']);
    }

    public function testForTypeRejectsUnsupportedProcedureTypes(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported medical procedure type.');

        (new MedicalProcedureConfig())->forType('unknown');
    }
}

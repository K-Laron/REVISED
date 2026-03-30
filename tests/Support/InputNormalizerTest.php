<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Support\InputNormalizer;
use PHPUnit\Framework\TestCase;

final class InputNormalizerTest extends TestCase
{
    public function testNullIfBlankReturnsNullForNullAndWhitespace(): void
    {
        self::assertNull(InputNormalizer::nullIfBlank(null));
        self::assertNull(InputNormalizer::nullIfBlank('   '));
        self::assertSame('value', InputNormalizer::nullIfBlank(' value '));
    }

    public function testBoolNormalizesCommonTruthyAndFalsyValues(): void
    {
        self::assertTrue(InputNormalizer::bool('true'));
        self::assertTrue(InputNormalizer::bool('1'));
        self::assertFalse(InputNormalizer::bool('false'));
        self::assertFalse(InputNormalizer::bool('unexpected'));
    }

    public function testDateTimeNormalizesDateAndMinutePrecisionInputs(): void
    {
        self::assertSame('2026-03-28 00:00:00', InputNormalizer::dateTime('2026-03-28'));
        self::assertSame('2026-03-28 14:45:00', InputNormalizer::dateTime('2026-03-28T14:45'));
        self::assertSame('2026-03-28 14:45:59', InputNormalizer::dateTime(' 2026-03-28 14:45:59 '));
    }

    public function testDateSupportsStrictAndLooseNormalization(): void
    {
        self::assertSame('2026-03-28', InputNormalizer::date('2026-03-28', true));
        self::assertNull(InputNormalizer::date('2026-03-28T14:45', true));
        self::assertSame('2026-03-28', InputNormalizer::date('2026-03-28T14:45'));
    }

    public function testNumericHelpersReturnNullForBlankValues(): void
    {
        self::assertNull(InputNormalizer::intOrNull(''));
        self::assertSame(12, InputNormalizer::intOrNull('12'));
        self::assertNull(InputNormalizer::decimalOrNull(null));
        self::assertSame(12.3, InputNormalizer::decimalOrNull('12.34', 1));
    }

    public function testFieldHelpersOnlyNormalizePresentValues(): void
    {
        $payload = [
            'scheduled_date' => '2026-03-28T09:15',
            'follow_up_date' => '2026-03-30T00:00',
            'notes' => 'keep',
        ];

        $payload = InputNormalizer::normalizeDateTimeFields($payload, ['scheduled_date', 'missing']);
        $payload = InputNormalizer::normalizeDateFields($payload, ['follow_up_date', 'another_missing']);

        self::assertSame('2026-03-28 09:15:00', $payload['scheduled_date']);
        self::assertSame('2026-03-30', $payload['follow_up_date']);
        self::assertSame('keep', $payload['notes']);
        self::assertArrayNotHasKey('missing', $payload);
    }

    public function testDaysSinceClampsInvalidAndFutureDates(): void
    {
        $now = strtotime('2026-03-28 12:00:00');

        self::assertSame(3, InputNormalizer::daysSince('2026-03-25 11:59:59', $now));
        self::assertSame(0, InputNormalizer::daysSince('invalid-date', $now));
        self::assertSame(0, InputNormalizer::daysSince('2026-03-29 00:00:00', $now));
    }
}

<?php

declare(strict_types=1);

namespace Tests\Support\Validation;

use App\Support\Validation\MedicalInputValidator;
use PHPUnit\Framework\TestCase;

final class MedicalInputValidatorTest extends TestCase
{
    public function testNormalizePayloadConvertsDateAndDateTimeFields(): void
    {
        $payload = (new MedicalInputValidator())->normalizePayload([
            'record_date' => '2026-04-03T09:15',
            'time_of_death' => '2026-04-03T10:30',
            'next_due_date' => '2026-04-10T00:00',
            'follow_up_date' => '2026-04-12T00:00',
            'start_date' => '2026-04-03T00:00',
            'end_date' => '2026-04-09T00:00',
        ]);

        self::assertSame('2026-04-03 09:15:00', $payload['record_date']);
        self::assertSame('2026-04-03 10:30:00', $payload['time_of_death']);
        self::assertSame('2026-04-10', $payload['next_due_date']);
        self::assertSame('2026-04-12', $payload['follow_up_date']);
        self::assertSame('2026-04-03', $payload['start_date']);
        self::assertSame('2026-04-09', $payload['end_date']);
    }

    public function testValidateForTypeRejectsUnsupportedAttachmentTypes(): void
    {
        $validator = (new MedicalInputValidator())->validateForType(
            [
                'animal_id' => '1',
                'record_date' => '2026-04-03 09:15:00',
                'vaccine_name' => 'Rabies',
                'dosage_ml' => '1',
                'route' => 'Subcutaneous',
                'dose_number' => '1',
            ],
            'vaccination',
            [
                'name' => ['result.pdf'],
                'size' => [1024],
            ],
            false
        );

        self::assertTrue($validator->fails());
        self::assertSame(
            'Lab result attachments must be JPG, JPEG, PNG, WebP, or GIF images.',
            $validator->errors()['lab_attachments'][0]
        );
    }

    public function testValidateForTypeRequiresTestNameWhenLabResultHasContent(): void
    {
        $validator = (new MedicalInputValidator())->validateForType(
            [
                'animal_id' => '1',
                'record_date' => '2026-04-03 09:15:00',
                'vaccine_name' => 'Rabies',
                'dosage_ml' => '1',
                'route' => 'Subcutaneous',
                'dose_number' => '1',
                'lab_results' => [
                    [
                        'result_value' => 'Positive',
                    ],
                ],
            ],
            'vaccination',
            null,
            false
        );

        self::assertTrue($validator->fails());
        self::assertSame('Each lab or imaging entry requires a test name.', $validator->errors()['lab_results'][0]);
    }
}

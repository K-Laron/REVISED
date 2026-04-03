<?php

declare(strict_types=1);

namespace Tests\Support\Validation;

use App\Support\Validation\AnimalInputValidator;
use PHPUnit\Framework\TestCase;

final class AnimalInputValidatorTest extends TestCase
{
    public function testValidateAnimalRequiresLocationForStrayIntake(): void
    {
        $validator = (new AnimalInputValidator())->validateAnimal(
            array_replace($this->basePayload(), [
                'intake_type' => 'Stray',
                'location_found' => '   ',
            ]),
            null
        );

        self::assertTrue($validator->fails());
        self::assertSame('Location found is required for stray intake.', $validator->errors()['location_found'][0]);
    }

    public function testValidateAnimalRequiresSurrenderReasonForOwnerSurrender(): void
    {
        $validator = (new AnimalInputValidator())->validateAnimal(
            array_replace($this->basePayload(), [
                'intake_type' => 'Owner Surrender',
                'surrender_reason' => '',
            ]),
            null
        );

        self::assertTrue($validator->fails());
        self::assertSame('Surrender reason is required for owner surrender.', $validator->errors()['surrender_reason'][0]);
    }

    public function testValidatePhotoUploadRejectsMoreThanFivePhotos(): void
    {
        $validator = (new AnimalInputValidator())->validatePhotoUpload([
            'name' => ['1.jpg', '2.jpg', '3.jpg', '4.jpg', '5.jpg', '6.jpg'],
            'size' => [1, 1, 1, 1, 1, 1],
        ]);

        self::assertTrue($validator->fails());
        self::assertSame('You may upload at most 5 photos.', $validator->errors()['photos'][0]);
    }

    private function basePayload(): array
    {
        return [
            'name' => 'Milo',
            'species' => 'Dog',
            'gender' => 'Male',
            'size' => 'Medium',
            'intake_type' => 'Stray',
            'intake_date' => '2026-04-03',
            'condition_at_intake' => 'Healthy',
            'temperament' => 'Friendly',
            'location_found' => 'Zone 1',
            'surrender_reason' => 'N/A',
        ];
    }
}

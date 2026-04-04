<?php

declare(strict_types=1);

namespace Tests\Views;

require_once __DIR__ . '/ViewSmokeTestCase.php';

final class AnimalsViewTest extends ViewSmokeTestCase
{
    public function testAnimalCreateViewKeepsIntakePhotoUploadSection(): void
    {
        $html = $this->renderApp('animals.create', [
            'breeds' => $this->breedsFixture(),
            'kennels' => $this->kennelsFixture(),
        ], '/animals/create');

        self::assertStringContainsString('Photo Upload', $html);
        self::assertStringContainsString('data-photo-input', $html);
        self::assertStringContainsString('Drag and drop photos here or click to browse.', $html);
    }

    public function testAnimalEditViewRendersPhotoUploadSection(): void
    {
        $html = $this->renderApp('animals.edit', [
            'animal' => $this->animalFixture(),
            'breeds' => $this->breedsFixture(),
            'kennels' => $this->kennelsFixture(),
        ], '/animals/7/edit');

        self::assertStringContainsString('animal-photo-upload-form', $html);
        self::assertStringContainsString('Upload more photos', $html);
        self::assertStringContainsString('data-photo-upload-input', $html);
        self::assertStringContainsString('data-animal-photo-action="delete"', $html);
        self::assertStringContainsString('data-animal-photo-action="make-primary"', $html);
        self::assertStringContainsString('animal-photo-library', $html);
        self::assertStringContainsString('draggable="true"', $html);
        self::assertStringContainsString('data-file-path="uploads/animals/7/animal-photo-1.jpg"', $html);
        self::assertStringContainsString('Drag to reorder', $html);
    }

    private function animalFixture(): array
    {
        return [
            'id' => 7,
            'animal_id' => 'AN-2026-0007',
            'name' => 'Luna',
            'species' => 'Dog',
            'breed_id' => 1,
            'breed_other' => null,
            'gender' => 'Female',
            'age_years' => 2,
            'age_months' => 3,
            'color_markings' => 'Brown',
            'size' => 'Medium',
            'weight_kg' => '15.5',
            'distinguishing_features' => 'White paws',
            'special_needs_notes' => null,
            'microchip_number' => null,
            'spay_neuter_status' => 'Unknown',
            'intake_type' => 'Stray',
            'intake_date' => '2026-04-03 09:00:00',
            'location_found' => 'Zone 1',
            'barangay_of_origin' => 'Poblacion',
            'impoundment_order_number' => null,
            'authority_name' => null,
            'authority_position' => null,
            'authority_contact' => null,
            'brought_by_name' => null,
            'brought_by_contact' => null,
            'brought_by_address' => null,
            'impounding_officer_name' => null,
            'surrender_reason' => null,
            'condition_at_intake' => 'Healthy',
            'vaccination_status_at_intake' => 'Unknown',
            'temperament' => 'Friendly',
            'current_kennel' => ['id' => 3],
            'photos' => [
                ['id' => 11, 'file_path' => 'uploads/animals/7/animal-photo-1.jpg', 'is_primary' => 1],
            ],
        ];
    }

    private function breedsFixture(): array
    {
        return [
            ['id' => 1, 'species' => 'Dog', 'name' => 'Aspin'],
        ];
    }

    private function kennelsFixture(): array
    {
        return [
            ['id' => 3, 'kennel_code' => 'K-03', 'zone' => 'A', 'size_category' => 'Medium'],
        ];
    }
}

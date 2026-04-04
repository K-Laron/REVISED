<?php

declare(strict_types=1);

namespace Tests\Integration\Animal;

require_once __DIR__ . '/../DatabaseIntegrationTestCase.php';

use App\Core\Database;
use App\Services\AnimalService;
use Tests\Integration\DatabaseIntegrationTestCase;

final class AnimalServiceIntegrationTest extends DatabaseIntegrationTestCase
{
    public function testGetReconcilesCompletedAdoptionAnimalStatus(): void
    {
        $adopter = $this->createUser('adopter');
        $animal = $this->createAnimal([
            'status' => 'Available',
            'status_reason' => 'Initial intake',
        ]);

        $this->createApplication([
            'adopter_id' => (int) $adopter['id'],
            'animal_id' => (int) $animal['id'],
            'status' => 'completed',
        ]);

        $before = Database::fetch('SELECT status FROM animals WHERE id = :id LIMIT 1', ['id' => $animal['id']]);
        self::assertIsArray($before);
        self::assertSame('Available', $before['status']);

        $resolved = (new AnimalService())->get((string) $animal['id']);

        self::assertSame('Adopted', $resolved['status']);

        $after = Database::fetch(
            'SELECT status, status_reason, outcome_date
             FROM animals
             WHERE id = :id
             LIMIT 1',
            ['id' => $animal['id']]
        );
        self::assertIsArray($after);
        self::assertSame('Adopted', $after['status']);
        self::assertSame('Adoption application completed.', $after['status_reason']);
        self::assertNotNull($after['outcome_date']);
    }

    public function testDeletePhotoReassignsPrimaryAndSortOrder(): void
    {
        $service = new AnimalService();
        $user = $this->createUser('super_admin');
        $animal = $this->createAnimal();
        $photos = [
            $this->createAnimalPhoto((int) $animal['id'], 'uploads/animals/' . $animal['id'] . '/integration-primary.png', 1, 0),
            $this->createAnimalPhoto((int) $animal['id'], 'uploads/animals/' . $animal['id'] . '/integration-secondary.png', 0, 1),
        ];

        $service->deletePhoto(
            (int) $animal['id'],
            (int) $photos[0]['id'],
            (int) $user['id'],
            $this->makeRequest(attributes: ['auth_user' => $user])
        );

        $remaining = Database::fetchAll(
            'SELECT id, is_primary, sort_order
             FROM animal_photos
             WHERE animal_id = :animal_id
             ORDER BY sort_order ASC, id ASC',
            ['animal_id' => $animal['id']]
        );

        self::assertCount(1, $remaining);
        self::assertSame((int) $photos[1]['id'], (int) $remaining[0]['id']);
        self::assertSame(1, (int) $remaining[0]['is_primary']);
        self::assertSame(0, (int) $remaining[0]['sort_order']);
    }

    public function testReorderPhotosPersistsOrderAndPrimaryPhoto(): void
    {
        $service = new AnimalService();
        $user = $this->createUser('super_admin');
        $animal = $this->createAnimal();
        $photos = [
            $this->createAnimalPhoto((int) $animal['id'], 'uploads/animals/' . $animal['id'] . '/integration-one.png', 1, 0),
            $this->createAnimalPhoto((int) $animal['id'], 'uploads/animals/' . $animal['id'] . '/integration-two.png', 0, 1),
            $this->createAnimalPhoto((int) $animal['id'], 'uploads/animals/' . $animal['id'] . '/integration-three.png', 0, 2),
        ];

        $updated = $service->reorderPhotos(
            (int) $animal['id'],
            [(int) $photos[2]['id'], (int) $photos[0]['id'], (int) $photos[1]['id']],
            (int) $user['id'],
            $this->makeRequest(attributes: ['auth_user' => $user])
        );

        self::assertSame((int) $photos[2]['id'], (int) $updated[0]['id']);
        self::assertSame(1, (int) $updated[0]['is_primary']);
        self::assertSame((int) $photos[0]['id'], (int) $updated[1]['id']);
        self::assertSame((int) $photos[1]['id'], (int) $updated[2]['id']);

        $rows = Database::fetchAll(
            'SELECT id, is_primary, sort_order
             FROM animal_photos
             WHERE animal_id = :animal_id
             ORDER BY sort_order ASC, id ASC',
            ['animal_id' => $animal['id']]
        );

        self::assertSame((int) $photos[2]['id'], (int) $rows[0]['id']);
        self::assertSame(1, (int) $rows[0]['is_primary']);
        self::assertSame(0, (int) $rows[0]['sort_order']);
        self::assertSame((int) $photos[0]['id'], (int) $rows[1]['id']);
        self::assertSame(1, (int) $rows[1]['sort_order']);
        self::assertSame((int) $photos[1]['id'], (int) $rows[2]['id']);
        self::assertSame(2, (int) $rows[2]['sort_order']);
    }

    private function createAnimalPhoto(int $animalId, string $relativePath, int $isPrimary, int $sortOrder): array
    {
        $absolutePath = $this->absolutePathFor($relativePath);
        $directory = dirname($absolutePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        file_put_contents($absolutePath, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+Vr2QAAAAASUVORK5CYII='));
        $this->trackRelativePath($relativePath);

        Database::execute(
            'INSERT INTO animal_photos (
                animal_id, file_path, file_name, file_size_bytes, mime_type, is_primary, sort_order, uploaded_by
             ) VALUES (
                :animal_id, :file_path, :file_name, :file_size_bytes, :mime_type, :is_primary, :sort_order, :uploaded_by
             )',
            [
                'animal_id' => $animalId,
                'file_path' => $relativePath,
                'file_name' => basename($relativePath),
                'file_size_bytes' => filesize($absolutePath) ?: 0,
                'mime_type' => 'image/png',
                'is_primary' => $isPrimary,
                'sort_order' => $sortOrder,
                'uploaded_by' => null,
            ]
        );

        $photoId = (int) Database::lastInsertId();

        return Database::fetch('SELECT * FROM animal_photos WHERE id = :id LIMIT 1', ['id' => $photoId]) ?: [];
    }
}

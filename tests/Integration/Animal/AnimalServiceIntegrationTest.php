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
}

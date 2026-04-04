<?php

declare(strict_types=1);

namespace App\Services\Animal;

use App\Core\Database;
use App\Models\Animal;
use RuntimeException;

final class AnimalKennelCoordinator
{
    /** @var callable(int): void */
    private $availabilityChecker;

    public function __construct(?Animal $animals = null, ?callable $availabilityChecker = null)
    {
        $this->animals = $animals ?? new Animal();
        $this->availabilityChecker = $availabilityChecker ?? [$this, 'assertKennelAvailable'];
    }

    private Animal $animals;

    public function syncAssignment(int $animalId, mixed $currentKennelId, mixed $newKennelId, int $userId): void
    {
        if ((string) ($newKennelId ?? '') === (string) ($currentKennelId ?? '')) {
            return;
        }

        if ($newKennelId !== null) {
            ($this->availabilityChecker)((int) $newKennelId);
        }

        if ($currentKennelId !== null) {
            $this->animals->releaseKennelOccupancy($animalId, $userId);
        }

        if ($newKennelId !== null) {
            $this->animals->assignKennel($animalId, (int) $newKennelId, $userId);
        }
    }

    private function assertKennelAvailable(int $kennelId): void
    {
        $kennel = Database::fetch(
            'SELECT id, status FROM kennels WHERE id = :id AND is_deleted = 0 LIMIT 1',
            ['id' => $kennelId]
        );

        if ($kennel === false) {
            throw new RuntimeException('Selected kennel was not found.');
        }

        if (($kennel['status'] ?? '') !== 'Available') {
            throw new RuntimeException('Selected kennel is not available.');
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Animal;

use App\Models\Animal;
use App\Models\Kennel;
use RuntimeException;

final class AnimalKennelCoordinator
{
    /** @var callable(int): void */
    private $availabilityChecker;

    public function __construct(
        private readonly Animal $animals,
        private readonly Kennel $kennels,
        ?callable $availabilityChecker = null
    ) {
        $this->availabilityChecker = $availabilityChecker ?? [$this, 'assertKennelAvailable'];
    }

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
        if (!$this->kennels->isAvailable($kennelId)) {
            throw new RuntimeException('Selected kennel is not available or does not exist.');
        }
    }
}

<?php

declare(strict_types=1);

namespace Tests\Services\Animal;

use App\Models\Animal;
use App\Models\Kennel;
use App\Services\Animal\AnimalKennelCoordinator;
use PHPUnit\Framework\TestCase;

final class AnimalKennelCoordinatorTest extends TestCase
{
    public function testSyncAssignmentReleasesCurrentKennelBeforeAssigningNewOne(): void
    {
        $animals = new class extends Animal {
            public array $released = [];
            public array $assigned = [];

            public function releaseKennelOccupancy(int $animalId, ?int $userId): void
            {
                $this->released[] = [$animalId, $userId];
            }

            public function assignKennel(int $animalId, ?int $kennelId, ?int $userId): void
            {
                $this->assigned[] = [$animalId, $kennelId, $userId];
            }
        };

        $checkedKennels = [];
        $kennels = $this->createMock(Kennel::class);
        $coordinator = new AnimalKennelCoordinator(
            $animals,
            $kennels,
            static function (int $kennelId) use (&$checkedKennels): void {
                $checkedKennels[] = $kennelId;
            }
        );

        $coordinator->syncAssignment(18, 3, 7, 12);

        self::assertSame([7], $checkedKennels);
        self::assertSame([[18, 12]], $animals->released);
        self::assertSame([[18, 7, 12]], $animals->assigned);
    }

    public function testSyncAssignmentDoesNothingWhenKennelDoesNotChange(): void
    {
        $animals = new class extends Animal {
            public int $releaseCalls = 0;
            public int $assignCalls = 0;

            public function releaseKennelOccupancy(int $animalId, ?int $userId): void
            {
                $this->releaseCalls++;
            }

            public function assignKennel(int $animalId, ?int $kennelId, ?int $userId): void
            {
                $this->assignCalls++;
            }
        };

        $kennels = $this->createMock(Kennel::class);
        $coordinator = new AnimalKennelCoordinator($animals, $kennels, static fn (int $kennelId): null => null);
        $coordinator->syncAssignment(18, 3, 3, 12);

        self::assertSame(0, $animals->releaseCalls);
        self::assertSame(0, $animals->assignCalls);
    }
}

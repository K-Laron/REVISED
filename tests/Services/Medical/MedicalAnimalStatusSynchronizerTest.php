<?php

declare(strict_types=1);

namespace Tests\Services\Medical;

use App\Models\Animal;
use App\Services\Medical\MedicalAnimalStatusSynchronizer;
use PHPUnit\Framework\TestCase;

final class MedicalAnimalStatusSynchronizerTest extends TestCase
{
    public function testSyncAfterWriteMarksEuthanasiaRecordsAsDeceased(): void
    {
        $animals = $this->createMock(Animal::class);
        $animals->expects(self::once())
            ->method('find')
            ->with(9)
            ->willReturn(['status' => 'Under Medical Care']);

        $animals->expects(self::once())
            ->method('updateStatus')
            ->with(9, 'Deceased', 'Euthanasia record added.', '2026-04-03 11:30:00', 4);

        $synchronizer = new MedicalAnimalStatusSynchronizer($animals);
        $synchronizer->syncAfterWrite('euthanasia', 9, ['time_of_death' => '2026-04-03 11:30:00'], 4);
    }

    public function testSyncAfterWritePromotesTreatmentRecordsToUnderMedicalCare(): void
    {
        $animals = $this->createMock(Animal::class);
        $animals->expects(self::once())
            ->method('find')
            ->with(9)
            ->willReturn(['status' => 'Available']);

        $animals->expects(self::once())
            ->method('updateStatus')
            ->with(9, 'Under Medical Care', 'Treatment record added.', null, 4);

        $synchronizer = new MedicalAnimalStatusSynchronizer($animals);
        $synchronizer->syncAfterWrite('treatment', 9, [], 4);
    }
}

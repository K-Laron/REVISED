<?php

declare(strict_types=1);

namespace Tests\Services\Medical;

use App\Models\DewormingRecord;
use App\Models\EuthanasiaRecord;
use App\Models\ExaminationRecord;
use App\Models\SurgeryRecord;
use App\Models\TreatmentRecord;
use App\Models\VaccinationRecord;
use App\Services\Medical\MedicalSubtypePersister;
use PHPUnit\Framework\TestCase;

final class MedicalSubtypePersisterTest extends TestCase
{
    public function testRecordReturnsDetailsFromMatchingSubtypeModel(): void
    {
        $treatments = $this->createMock(TreatmentRecord::class);
        $treatments->expects(self::once())
            ->method('findByMedicalRecord')
            ->with(41)
            ->willReturn(['diagnosis' => 'Skin infection']);

        $persister = new MedicalSubtypePersister(
            $this->createStub(VaccinationRecord::class),
            $this->createStub(SurgeryRecord::class),
            $this->createStub(ExaminationRecord::class),
            $treatments,
            $this->createStub(DewormingRecord::class),
            $this->createStub(EuthanasiaRecord::class)
        );

        self::assertSame(['diagnosis' => 'Skin infection'], $persister->record(41, 'treatment'));
    }

    public function testPersistDelegatesCreateToMatchingSubtypeModel(): void
    {
        $vaccinations = $this->createMock(VaccinationRecord::class);
        $vaccinations->expects(self::once())
            ->method('create')
            ->with(['medical_record_id' => 18, 'vaccine_name' => 'Rabies']);

        $persister = new MedicalSubtypePersister(
            $vaccinations,
            $this->createStub(SurgeryRecord::class),
            $this->createStub(ExaminationRecord::class),
            $this->createStub(TreatmentRecord::class),
            $this->createStub(DewormingRecord::class),
            $this->createStub(EuthanasiaRecord::class)
        );

        $persister->persist('vaccination', 18, ['medical_record_id' => 18, 'vaccine_name' => 'Rabies'], true);
    }
}

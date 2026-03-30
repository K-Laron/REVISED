<?php

declare(strict_types=1);

namespace Tests\Services\Medical;

use App\Models\TreatmentRecord;
use App\Services\Medical\MedicalPayloadFactory;
use PHPUnit\Framework\TestCase;

final class MedicalPayloadFactoryTest extends TestCase
{
    public function testBasePayloadNormalizesSharedMedicalFields(): void
    {
        $factory = new MedicalPayloadFactory($this->createStub(TreatmentRecord::class));

        self::assertSame(
            [
                'animal_id' => 12,
                'procedure_type' => 'vaccination',
                'record_date' => '2026-03-28 09:15:00',
                'general_notes' => null,
                'veterinarian_id' => 5,
                'created_by' => 99,
                'updated_by' => 99,
            ],
            $factory->basePayload('vaccination', [
                'animal_id' => '12',
                'record_date' => '2026-03-28T09:15',
                'general_notes' => '   ',
                'veterinarian_id' => '5',
            ], 99, true)
        );
    }

    public function testSubtypePayloadDefaultsVaccinationDueDateFromRecordDate(): void
    {
        $factory = new MedicalPayloadFactory($this->createStub(TreatmentRecord::class));

        self::assertSame(
            [
                'medical_record_id' => 77,
                'vaccine_name' => 'Rabies',
                'vaccine_brand' => null,
                'batch_lot_number' => null,
                'dosage_ml' => 1.25,
                'route' => 'Subcutaneous',
                'injection_site' => null,
                'dose_number' => 2,
                'next_due_date' => '2027-03-28',
                'adverse_reactions' => null,
            ],
            $factory->subtypePayload('vaccination', [
                'record_date' => '2026-03-28T09:15',
                'vaccine_name' => ' Rabies ',
                'vaccine_brand' => '',
                'batch_lot_number' => '',
                'dosage_ml' => '1.25',
                'route' => 'Subcutaneous',
                'injection_site' => '',
                'dose_number' => '2',
                'next_due_date' => '',
                'adverse_reactions' => ' ',
            ], 77, true)
        );
    }

    public function testSubtypePayloadFallsBackToExistingTreatmentInventoryValuesOnUpdate(): void
    {
        $treatments = $this->createMock(TreatmentRecord::class);
        $treatments->expects(self::once())
            ->method('findByMedicalRecord')
            ->with(55)
            ->willReturn([
                'inventory_item_id' => 9,
                'quantity_dispensed' => 4,
            ]);

        $factory = new MedicalPayloadFactory($treatments);

        $payload = $factory->subtypePayload('treatment', [
            'diagnosis' => 'Skin issue',
            'medication_name' => 'Antibiotic',
            'dosage' => '5 ml',
            'route' => 'Oral',
            'frequency' => 'Twice daily',
            'duration_days' => '',
            'start_date' => '2026-03-28',
            'end_date' => '',
            'quantity_dispensed' => '',
            'inventory_item_id' => '',
            'special_instructions' => ' ',
        ], 55, false);

        self::assertSame(9, $payload['inventory_item_id']);
        self::assertSame(4, $payload['quantity_dispensed']);
        self::assertSame('2026-03-28', $payload['start_date']);
        self::assertNull($payload['end_date']);
    }
}

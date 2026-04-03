<?php

declare(strict_types=1);

namespace Tests\Services\Medical;

use App\Models\MedicalLabResult;
use App\Models\MedicalPrescription;
use App\Models\VitalSign;
use App\Services\Medical\MedicalAttachmentManager;
use App\Services\Medical\MedicalSharedSectionPersister;
use PHPUnit\Framework\TestCase;

final class MedicalSharedSectionPersisterTest extends TestCase
{
    public function testSavePersistsSharedSectionsAndReturnsAttachmentSync(): void
    {
        $vitalSigns = $this->createMock(VitalSign::class);
        $vitalSigns->expects(self::once())
            ->method('upsert')
            ->with(55, [
                'weight_kg' => 12.5,
                'temperature_celsius' => 38.2,
                'heart_rate_bpm' => 90,
                'respiratory_rate' => 20,
                'body_condition_score' => 5,
            ]);

        $prescriptions = $this->createMock(MedicalPrescription::class);
        $prescriptions->expects(self::once())
            ->method('bulkReplaceForRecord')
            ->with(55, [
                ['medicine_name' => 'Amoxicillin', 'dosage' => '5 ml'],
            ]);

        $labResults = $this->createMock(MedicalLabResult::class);
        $labResults->expects(self::once())
            ->method('bulkReplaceForRecord')
            ->with(55, [
                ['test_name' => 'CBC', 'result_value' => 'Normal'],
            ]);

        $attachments = $this->createMock(MedicalAttachmentManager::class);
        $attachments->expects(self::once())
            ->method('syncLabResults')
            ->with(
                55,
                [
                    ['test_name' => 'CBC', 'result_value' => 'Normal'],
                ],
                null,
                [
                    ['attachment_path' => 'uploads/old.png'],
                ]
            )
            ->willReturn([
                'rows' => [
                    ['test_name' => 'CBC', 'result_value' => 'Normal'],
                ],
                'new_files' => ['uploads/new.png'],
                'obsolete_files' => ['uploads/old.png'],
            ]);

        $persister = new MedicalSharedSectionPersister($vitalSigns, $prescriptions, $labResults, $attachments);
        $result = $persister->save(55, [
            'vs_weight_kg' => '12.5',
            'vs_temperature_celsius' => '38.2',
            'vs_heart_rate_bpm' => '90',
            'vs_respiratory_rate' => '20',
            'vs_body_condition_score' => '5',
            'prescriptions' => json_encode([
                ['medicine_name' => 'Amoxicillin', 'dosage' => '5 ml'],
            ], JSON_THROW_ON_ERROR),
            'lab_results' => json_encode([
                ['test_name' => 'CBC', 'result_value' => 'Normal'],
            ], JSON_THROW_ON_ERROR),
        ], null, [
            ['attachment_path' => 'uploads/old.png'],
        ]);

        self::assertSame(['uploads/new.png'], $result['new_files']);
        self::assertSame(['uploads/old.png'], $result['obsolete_files']);
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Medical;

use App\Models\DewormingRecord;
use App\Models\EuthanasiaRecord;
use App\Models\ExaminationRecord;
use App\Models\SurgeryRecord;
use App\Models\TreatmentRecord;
use App\Models\VaccinationRecord;
use RuntimeException;

final class MedicalSubtypePersister
{
    public function __construct(
        private readonly VaccinationRecord $vaccinations,
        private readonly SurgeryRecord $surgeries,
        private readonly ExaminationRecord $examinations,
        private readonly TreatmentRecord $treatments,
        private readonly DewormingRecord $dewormings,
        private readonly EuthanasiaRecord $euthanasias
    ) {
    }

    public function record(int $medicalRecordId, string $type): array
    {
        return match ($type) {
            'vaccination' => $this->vaccinations->findByMedicalRecord($medicalRecordId) ?: [],
            'surgery' => $this->surgeries->findByMedicalRecord($medicalRecordId) ?: [],
            'examination' => $this->examinations->findByMedicalRecord($medicalRecordId) ?: [],
            'treatment' => $this->treatments->findByMedicalRecord($medicalRecordId) ?: [],
            'deworming' => $this->dewormings->findByMedicalRecord($medicalRecordId) ?: [],
            'euthanasia' => $this->euthanasias->findByMedicalRecord($medicalRecordId) ?: [],
            default => [],
        };
    }

    public function persist(string $type, int $medicalRecordId, array $payload, bool $creating): void
    {
        if ($creating) {
            match ($type) {
                'vaccination' => $this->vaccinations->create($payload),
                'surgery' => $this->surgeries->create($payload),
                'examination' => $this->examinations->create($payload),
                'treatment' => $this->treatments->create($payload),
                'deworming' => $this->dewormings->create($payload),
                'euthanasia' => $this->euthanasias->create($payload),
                default => throw new RuntimeException('Unsupported medical procedure type.'),
            };

            return;
        }

        match ($type) {
            'vaccination' => $this->vaccinations->updateByMedicalRecord($medicalRecordId, $payload),
            'surgery' => $this->surgeries->updateByMedicalRecord($medicalRecordId, $payload),
            'examination' => $this->examinations->updateByMedicalRecord($medicalRecordId, $payload),
            'treatment' => $this->treatments->updateByMedicalRecord($medicalRecordId, $payload),
            'deworming' => $this->dewormings->updateByMedicalRecord($medicalRecordId, $payload),
            'euthanasia' => $this->euthanasias->updateByMedicalRecord($medicalRecordId, $payload),
            default => throw new RuntimeException('Unsupported medical procedure type.'),
        };
    }
}

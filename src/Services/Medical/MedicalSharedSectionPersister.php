<?php

declare(strict_types=1);

namespace App\Services\Medical;

use App\Models\MedicalLabResult;
use App\Models\MedicalPrescription;
use App\Models\VitalSign;
use App\Support\InputNormalizer;

final class MedicalSharedSectionPersister
{
    public function __construct(
        private readonly VitalSign $vitalSigns,
        private readonly MedicalPrescription $prescriptions,
        private readonly MedicalLabResult $labResults,
        private readonly MedicalAttachmentManager $attachments
    ) {
    }

    public function save(int $medicalRecordId, array $data, mixed $labAttachmentInput = null, array $existingLabResults = []): array
    {
        $attachmentSync = [
            'new_files' => [],
            'obsolete_files' => [],
        ];

        $hasVitalData = false;
        foreach (['vs_weight_kg', 'vs_temperature_celsius', 'vs_heart_rate_bpm', 'vs_respiratory_rate', 'vs_body_condition_score'] as $field) {
            if (($data[$field] ?? '') !== '') {
                $hasVitalData = true;
                break;
            }
        }

        if ($hasVitalData) {
            $this->vitalSigns->upsert($medicalRecordId, [
                'weight_kg' => InputNormalizer::decimalOrNull($data['vs_weight_kg'] ?? null),
                'temperature_celsius' => InputNormalizer::decimalOrNull($data['vs_temperature_celsius'] ?? null, 1),
                'heart_rate_bpm' => InputNormalizer::intOrNull($data['vs_heart_rate_bpm'] ?? null),
                'respiratory_rate' => InputNormalizer::intOrNull($data['vs_respiratory_rate'] ?? null),
                'body_condition_score' => InputNormalizer::intOrNull($data['vs_body_condition_score'] ?? null),
            ]);
        }

        $prescriptionsRaw = $data['prescriptions'] ?? [];
        if (is_string($prescriptionsRaw)) {
            $prescriptionsRaw = json_decode($prescriptionsRaw, true) ?: [];
        }
        if (is_array($prescriptionsRaw)) {
            $this->prescriptions->bulkReplaceForRecord($medicalRecordId, $prescriptionsRaw);
        }

        $labResultsRaw = $data['lab_results'] ?? [];
        if (is_string($labResultsRaw)) {
            $labResultsRaw = json_decode($labResultsRaw, true) ?: [];
        }
        if (is_array($labResultsRaw)) {
            $attachmentSync = $this->attachments->syncLabResults($medicalRecordId, $labResultsRaw, $labAttachmentInput, $existingLabResults);
            $this->labResults->bulkReplaceForRecord($medicalRecordId, $attachmentSync['rows']);
        }

        return $attachmentSync;
    }

    public function vitalSigns(int $medicalRecordId): array
    {
        return $this->vitalSigns->findByMedicalRecordId($medicalRecordId) ?: [];
    }

    public function prescriptions(int $medicalRecordId): array
    {
        return $this->prescriptions->findByMedicalRecordId($medicalRecordId);
    }

    public function labResults(int $medicalRecordId): array
    {
        return $this->attachments->normalizeLabResults($this->labResults->findByMedicalRecordId($medicalRecordId));
    }
}

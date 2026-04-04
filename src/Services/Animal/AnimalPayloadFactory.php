<?php

declare(strict_types=1);

namespace App\Services\Animal;

use App\Helpers\Sanitizer;

final class AnimalPayloadFactory
{
    public function build(array $data, int $userId, bool $creating = true): array
    {
        $intakeType = (string) ($data['intake_type'] ?? '');
        $showLocationFound = $intakeType === 'Stray';
        $showSurrenderReason = $intakeType === 'Owner Surrender';
        $showBroughtBy = in_array($intakeType, ['Owner Surrender', 'Confiscated', 'Transfer'], true);
        $showAuthority = in_array($intakeType, ['Stray', 'Confiscated'], true);

        return [
            'animal_id' => $data['animal_id'] ?? null,
            'name' => ($data['name'] ?? '') !== '' ? $data['name'] : null,
            'species' => (string) ($data['species'] ?? ''),
            'breed_id' => ($data['breed_id'] ?? '') !== '' ? (int) $data['breed_id'] : null,
            'breed_other' => ($data['breed_other'] ?? '') !== '' ? $data['breed_other'] : null,
            'gender' => (string) ($data['gender'] ?? ''),
            'age_years' => ($data['age_years'] ?? '') !== '' ? (int) $data['age_years'] : null,
            'age_months' => ($data['age_months'] ?? '') !== '' ? (int) $data['age_months'] : null,
            'color_markings' => ($data['color_markings'] ?? '') !== '' ? $data['color_markings'] : null,
            'size' => (string) ($data['size'] ?? ''),
            'weight_kg' => ($data['weight_kg'] ?? '') !== '' ? round((float) $data['weight_kg'], 2) : null,
            'distinguishing_features' => ($data['distinguishing_features'] ?? '') !== '' ? $data['distinguishing_features'] : null,
            'special_needs_notes' => ($data['special_needs_notes'] ?? '') !== '' ? $data['special_needs_notes'] : null,
            'microchip_number' => ($data['microchip_number'] ?? '') !== '' ? $data['microchip_number'] : null,
            'spay_neuter_status' => ($data['spay_neuter_status'] ?? '') !== '' ? $data['spay_neuter_status'] : 'Unknown',
            'intake_type' => $intakeType,
            'intake_date' => str_contains((string) ($data['intake_date'] ?? ''), 'T')
                ? str_replace('T', ' ', (string) $data['intake_date']) . ':00'
                : (string) ($data['intake_date'] ?? ''),
            'location_found' => $showLocationFound && ($data['location_found'] ?? '') !== '' ? $data['location_found'] : null,
            'barangay_of_origin' => ($data['barangay_of_origin'] ?? '') !== '' ? $data['barangay_of_origin'] : null,
            'impoundment_order_number' => $showAuthority && ($data['impoundment_order_number'] ?? '') !== '' ? $data['impoundment_order_number'] : null,
            'authority_name' => $showAuthority && ($data['authority_name'] ?? '') !== '' ? $data['authority_name'] : null,
            'authority_position' => $showAuthority && ($data['authority_position'] ?? '') !== '' ? $data['authority_position'] : null,
            'authority_contact' => $showAuthority ? Sanitizer::phone($data['authority_contact'] ?? null) : null,
            'brought_by_name' => $showBroughtBy && ($data['brought_by_name'] ?? '') !== '' ? $data['brought_by_name'] : null,
            'brought_by_contact' => $showBroughtBy ? Sanitizer::phone($data['brought_by_contact'] ?? null) : null,
            'brought_by_address' => $showBroughtBy && ($data['brought_by_address'] ?? '') !== '' ? $data['brought_by_address'] : null,
            'impounding_officer_name' => ($data['impounding_officer_name'] ?? '') !== '' ? $data['impounding_officer_name'] : null,
            'surrender_reason' => $showSurrenderReason && ($data['surrender_reason'] ?? '') !== '' ? $data['surrender_reason'] : null,
            'condition_at_intake' => (string) ($data['condition_at_intake'] ?? ''),
            'vaccination_status_at_intake' => ($data['vaccination_status_at_intake'] ?? '') !== '' ? $data['vaccination_status_at_intake'] : 'Unknown',
            'temperament' => ($data['temperament'] ?? '') !== '' ? $data['temperament'] : 'Unknown',
            'status' => $creating ? 'Available' : ($data['status'] ?? 'Available'),
            'status_reason' => $creating ? 'Initial intake' : ($data['status_reason'] ?? null),
            'status_changed_at' => $creating ? date('Y-m-d H:i:s') : ($data['status_changed_at'] ?? null),
            'created_by' => $creating ? $userId : null,
            'updated_by' => $userId,
            'kennel_id' => ($data['kennel_id'] ?? '') !== '' ? (int) $data['kennel_id'] : null,
        ];
    }
}

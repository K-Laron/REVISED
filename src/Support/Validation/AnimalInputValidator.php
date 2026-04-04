<?php

declare(strict_types=1);

namespace App\Support\Validation;

use App\Helpers\Validator;

final class AnimalInputValidator
{
    public function validateAnimal(array $data, mixed $photos = null): Validator
    {
        $validator = (new Validator($data))->rules([
            'name' => 'nullable|string|max:100',
            'species' => 'required|in:Dog,Cat,Other',
            'breed_id' => 'nullable|integer|exists:breeds,id',
            'breed_other' => 'nullable|string|max:100',
            'gender' => 'required|in:Male,Female',
            'age_years' => 'nullable|integer|between:0,30',
            'age_months' => 'nullable|integer|between:0,11',
            'color_markings' => 'nullable|string|max:255',
            'size' => 'required|in:Small,Medium,Large,Extra Large',
            'weight_kg' => 'nullable|numeric|between:0.1,150',
            'distinguishing_features' => 'nullable|string|max:1000',
            'special_needs_notes' => 'nullable|string|max:2000',
            'microchip_number' => 'nullable|string|max:50',
            'spay_neuter_status' => 'nullable|in:Yes,No,Unknown',
            'intake_type' => 'required|in:Stray,Owner Surrender,Confiscated,Transfer,Born in Shelter',
            'intake_date' => 'required|string',
            'location_found' => 'nullable|string|max:500',
            'barangay_of_origin' => 'nullable|string|max:100',
            'impoundment_order_number' => 'nullable|string|max:50',
            'authority_name' => 'nullable|string|max:200',
            'authority_position' => 'nullable|string|max:100',
            'authority_contact' => 'nullable|phone_ph',
            'brought_by_name' => 'nullable|string|max:200',
            'brought_by_contact' => 'nullable|phone_ph',
            'brought_by_address' => 'nullable|string|max:500',
            'impounding_officer_name' => 'nullable|string|max:200',
            'surrender_reason' => 'nullable|string|max:1000',
            'condition_at_intake' => 'required|in:Healthy,Injured,Sick,Malnourished,Aggressive',
            'vaccination_status_at_intake' => 'nullable|in:Up to date,Partial,None,Unknown',
            'temperament' => 'required|in:Friendly,Shy,Aggressive,Unknown',
            'kennel_id' => 'nullable|integer|exists:kennels,id',
        ]);

        if (($data['intake_type'] ?? null) === 'Stray' && trim((string) ($data['location_found'] ?? '')) === '') {
            $validator->addManualError('location_found', 'Location found is required for stray intake.');
        }

        if (($data['intake_type'] ?? null) === 'Owner Surrender' && trim((string) ($data['surrender_reason'] ?? '')) === '') {
            $validator->addManualError('surrender_reason', 'Surrender reason is required for owner surrender.');
        }

        $this->validatePhotos($validator, $photos);

        return $validator;
    }

    public function validateStatus(array $data): Validator
    {
        return (new Validator($data))->rules([
            'status' => 'required|in:Available,Under Medical Care,In Adoption Process,Adopted,Deceased,Transferred,Quarantine',
            'status_reason' => 'nullable|string|max:500',
        ]);
    }

    public function validatePhotoUpload(mixed $files): Validator
    {
        $validator = new Validator([]);
        $this->validatePhotos($validator, $files);

        return $validator;
    }

    public function validatePhotoReorder(array $data): Validator
    {
        $validator = (new Validator($data))->rules([
            'photo_ids' => 'required|array|min:1|max:5',
        ]);

        $photoIds = $data['photo_ids'] ?? null;
        if (!is_array($photoIds)) {
            return $validator;
        }

        foreach ($photoIds as $photoId) {
            if (filter_var($photoId, FILTER_VALIDATE_INT) === false) {
                $validator->addManualError('photo_ids', 'Each photo id must be an integer.');
                return $validator;
            }
        }

        $normalizedIds = array_map('intval', $photoIds);
        if (count(array_unique($normalizedIds)) !== count($normalizedIds)) {
            $validator->addManualError('photo_ids', 'Photo order must contain unique photo ids.');
        }

        return $validator;
    }

    private function validatePhotos(Validator $validator, mixed $files): void
    {
        if ($files === null || !is_array($files) || !isset($files['name'])) {
            return;
        }

        $names = is_array($files['name']) ? $files['name'] : [$files['name']];
        $sizes = is_array($files['size']) ? $files['size'] : [$files['size']];

        if (count(array_filter($names)) > 5) {
            $validator->addManualError('photos', 'You may upload at most 5 photos.');
        }

        foreach ($names as $index => $name) {
            if (!$name) {
                continue;
            }

            $extension = strtolower(pathinfo((string) $name, PATHINFO_EXTENSION));
            if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                $validator->addManualError('photos', 'Photos must be JPG, JPEG, PNG, or WebP.');
                break;
            }

            if ((int) ($sizes[$index] ?? 0) > (5 * 1024 * 1024)) {
                $validator->addManualError('photos', 'Each photo must not exceed 5MB.');
                break;
            }
        }
    }
}

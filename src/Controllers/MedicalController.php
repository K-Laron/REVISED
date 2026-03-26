<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Helpers\Validator;
use App\Middleware\CsrfMiddleware;
use App\Services\MedicalService;
use RuntimeException;

class MedicalController
{
    private MedicalService $medical;

    public function __construct()
    {
        $this->medical = new MedicalService();
    }

    public function index(Request $request): Response
    {
        return Response::html(View::render('medical.index', [
            'title' => 'Medical Records',
            'extraCss' => ['/assets/css/medical.css'],
            'extraJs' => ['/assets/js/medical.js'],
            'csrfToken' => CsrfMiddleware::token(),
            'animals' => $this->medical->animalOptions(),
            'practitioners' => $this->medical->practitioners(),
            'procedureTypes' => $this->procedureTypes(),
        ], 'layouts.app'));
    }

    public function create(Request $request, string $animalId): Response
    {
        $animal = null;
        foreach ($this->medical->animalOptions() as $option) {
            if ((int) $option['id'] === (int) $animalId) {
                $animal = $option;
                break;
            }
        }

        if ($animal === null) {
            return Response::redirect('/medical');
        }

        return Response::html(View::render('medical.create', [
            'title' => 'New Medical Record',
            'extraCss' => ['/assets/css/medical.css'],
            'extraJs' => ['/assets/js/medical.js'],
            'csrfToken' => CsrfMiddleware::token(),
            'animal' => $animal,
            'record' => null,
            'practitioners' => $this->medical->practitioners(),
            'inventoryItems' => $this->medical->treatmentInventoryOptions(),
            'procedureTypes' => $this->procedureTypes(),
            'formConfigs' => $this->allFormConfigs(),
        ], 'layouts.app'));
    }

    public function show(Request $request, string $id): Response
    {
        try {
            $record = $this->medical->get((int) $id);
        } catch (RuntimeException) {
            return Response::redirect('/medical');
        }

        return Response::html(View::render('medical.show', [
            'title' => 'Medical Record ' . $record['id'],
            'extraCss' => ['/assets/css/medical.css'],
            'extraJs' => ['/assets/js/medical.js'],
            'csrfToken' => CsrfMiddleware::token(),
            'record' => $record,
            'animal' => [
                'id' => $record['animal_id'],
                'animal_id' => $record['animal_code'],
                'name' => $record['animal_name'],
                'status' => $record['animal_status'],
            ],
            'practitioners' => $this->medical->practitioners(),
            'inventoryItems' => $this->medical->treatmentInventoryOptions(),
            'procedureTypes' => $this->procedureTypes(),
            'formConfigs' => $this->allFormConfigs(),
        ], 'layouts.app'));
    }

    public function list(Request $request): Response
    {
        $page = max(1, (int) $request->query('page', 1));
        $perPage = max(1, min(100, (int) $request->query('per_page', 20)));
        $result = $this->medical->list($request->query(), $page, $perPage);

        return Response::success(
            $result['items'],
            'Medical records retrieved successfully.',
            [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $result['total'],
                'total_pages' => (int) ceil(max(1, $result['total']) / $perPage),
            ]
        );
    }

    public function byAnimal(Request $request, string $animalId): Response
    {
        try {
            $records = $this->medical->byAnimal((int) $animalId);
        } catch (RuntimeException $exception) {
            return Response::error(404, 'NOT_FOUND', $exception->getMessage());
        }

        return Response::success($records, 'Animal medical records retrieved successfully.');
    }

    public function storeVaccination(Request $request): Response
    {
        return $this->storeByType($request, 'vaccination');
    }

    public function storeSurgery(Request $request): Response
    {
        return $this->storeByType($request, 'surgery');
    }

    public function storeExamination(Request $request): Response
    {
        return $this->storeByType($request, 'examination');
    }

    public function storeTreatment(Request $request): Response
    {
        return $this->storeByType($request, 'treatment');
    }

    public function storeDeworming(Request $request): Response
    {
        return $this->storeByType($request, 'deworming');
    }

    public function storeEuthanasia(Request $request): Response
    {
        return $this->storeByType($request, 'euthanasia');
    }

    public function update(Request $request, string $id): Response
    {
        try {
            $current = $this->medical->get((int) $id);
        } catch (RuntimeException $exception) {
            return Response::error(404, 'NOT_FOUND', $exception->getMessage());
        }

        $payload = $this->normalizedValidationData($request->body() + [
            'animal_id' => $current['animal_id'],
        ]);
        $validator = $this->validatorForType($payload, (string) $current['procedure_type'], false);

        if ($validator->fails()) {
            return Response::error(422, 'VALIDATION_ERROR', 'The given data was invalid.', $validator->errors());
        }

        $authUser = $request->attribute('auth_user');

        try {
            $record = $this->medical->update((int) $id, $payload, (int) $authUser['id'], $request);
        } catch (RuntimeException $exception) {
            return Response::error(409, 'MEDICAL_UPDATE_BLOCKED', $exception->getMessage());
        }

        return Response::success([
            'record' => $record,
            'redirect' => '/medical/' . $record['id'],
        ], 'Medical record updated successfully.');
    }

    public function destroy(Request $request, string $id): Response
    {
        $authUser = $request->attribute('auth_user');

        try {
            $this->medical->delete((int) $id, (int) $authUser['id'], $request);
        } catch (RuntimeException $exception) {
            return Response::error(404, 'NOT_FOUND', $exception->getMessage());
        }

        return Response::success([], 'Medical record deleted successfully.');
    }

    public function dueVaccinations(Request $request): Response
    {
        return Response::success($this->medical->dueVaccinations(), 'Due vaccinations retrieved successfully.');
    }

    public function dueDewormings(Request $request): Response
    {
        return Response::success($this->medical->dueDewormings(), 'Due dewormings retrieved successfully.');
    }

    public function formConfig(Request $request, string $type): Response
    {
        try {
            $config = $this->medical->formConfig($type);
        } catch (RuntimeException $exception) {
            return Response::error(404, 'NOT_FOUND', $exception->getMessage());
        }

        return Response::success($config, 'Medical form config retrieved successfully.');
    }

    private function storeByType(Request $request, string $type): Response
    {
        $payload = $this->normalizedValidationData($request->body());
        $validator = $this->validatorForType($payload, $type, true);

        if ($validator->fails()) {
            return Response::error(422, 'VALIDATION_ERROR', 'The given data was invalid.', $validator->errors());
        }

        $authUser = $request->attribute('auth_user');

        try {
            $record = $this->medical->create($type, $payload, (int) $authUser['id'], $request);
        } catch (RuntimeException $exception) {
            return Response::error(409, 'MEDICAL_CREATE_BLOCKED', $exception->getMessage());
        }

        return Response::success([
            'record' => $record,
            'redirect' => '/medical/' . $record['id'],
        ], 'Medical record created successfully.');
    }

    private function validatorForType(array $payload, string $type, bool $creating): Validator
    {
        $rules = [
            'animal_id' => $creating ? 'required|integer|exists:animals,id' : 'required|integer',
            'record_date' => 'required|date',
            'general_notes' => 'nullable|string|max:2000',
            'veterinarian_id' => 'required|integer|exists:users,id',
            // Vital signs (shared across all procedure types)
            'vs_weight_kg' => 'nullable|numeric|between:0.1,150',
            'vs_temperature_celsius' => 'nullable|numeric|between:35,43',
            'vs_heart_rate_bpm' => 'nullable|integer|between:30,300',
            'vs_respiratory_rate' => 'nullable|integer|between:5,100',
            'vs_body_condition_score' => 'nullable|integer|between:1,9',
        ];

        $typeRules = match ($type) {
            'vaccination' => [
                'vaccine_name' => 'required|string|max:100',
                'vaccine_brand' => 'nullable|string|max:100',
                'batch_lot_number' => 'nullable|string|max:50',
                'dosage_ml' => 'required|numeric|between:0.01,100',
                'route' => 'required|in:Subcutaneous,Intramuscular,Oral',
                'injection_site' => 'nullable|string|max:50',
                'dose_number' => 'required|integer|between:1,10',
                'next_due_date' => 'nullable|date|after:record_date',
                'adverse_reactions' => 'nullable|string|max:1000',
            ],
            'surgery' => [
                'surgery_type' => 'required|in:Spay,Neuter,Tumor Removal,Amputation,Wound Repair,Other',
                'pre_op_weight_kg' => 'nullable|numeric|between:0.1,150',
                'anesthesia_type' => 'required|in:General,Local,Sedation',
                'anesthesia_drug' => 'nullable|string|max:100',
                'anesthesia_dosage' => 'nullable|string|max:50',
                'duration_minutes' => 'nullable|integer|between:1,1440',
                'surgical_notes' => 'nullable|string|max:2000',
                'complications' => 'nullable|string|max:1000',
                'post_op_instructions' => 'nullable|string|max:2000',
                'follow_up_date' => 'nullable|date|after:record_date',
            ],
            'examination' => [
                'weight_kg' => 'nullable|numeric|between:0.1,150',
                'temperature_celsius' => 'nullable|numeric|between:35,43',
                'heart_rate_bpm' => 'nullable|integer|between:30,300',
                'respiratory_rate' => 'nullable|integer|between:5,100',
                'body_condition_score' => 'nullable|integer|between:1,9',
                'eyes_status' => 'nullable|in:Normal,Abnormal',
                'eyes_notes' => 'nullable|string|max:1000',
                'ears_status' => 'nullable|in:Normal,Abnormal',
                'ears_notes' => 'nullable|string|max:1000',
                'teeth_gums_status' => 'nullable|in:Normal,Abnormal',
                'teeth_gums_notes' => 'nullable|string|max:1000',
                'skin_coat_status' => 'nullable|in:Normal,Abnormal',
                'skin_coat_notes' => 'nullable|string|max:1000',
                'musculoskeletal_status' => 'nullable|in:Normal,Abnormal',
                'musculoskeletal_notes' => 'nullable|string|max:1000',
                'overall_assessment' => 'nullable|string|max:2000',
                'recommendations' => 'nullable|string|max:2000',
            ],
            'treatment' => [
                'diagnosis' => 'required|string|max:255',
                'medication_name' => 'required|string|max:150',
                'dosage' => 'required|string|max:100',
                'route' => 'required|in:Oral,Injection,Topical,IV',
                'frequency' => 'required|string|max:50',
                'duration_days' => 'nullable|integer|between:1,365',
                'start_date' => 'required|date',
                'end_date' => 'nullable|date|after:start_date',
                'quantity_dispensed' => 'nullable|integer|between:1,1000',
                'inventory_item_id' => 'nullable|integer|exists:inventory_items,id',
                'special_instructions' => 'nullable|string|max:1000',
            ],
            'deworming' => [
                'dewormer_name' => 'required|string|max:100',
                'brand' => 'nullable|string|max:100',
                'dosage' => 'required|string|max:100',
                'weight_at_treatment_kg' => 'nullable|numeric|between:0.1,150',
                'next_due_date' => 'nullable|date|after:record_date',
            ],
            'euthanasia' => [
                'reason_category' => 'required|in:Medical,Behavioral,Legal/Court Order,Population Management',
                'reason_details' => 'required|string|min:10|max:2000',
                'authorized_by' => 'required|integer|exists:users,id',
                'method' => 'required|string|max:50',
                'drug_used' => 'nullable|string|max:100',
                'drug_dosage' => 'nullable|string|max:50',
                'time_of_death' => 'required|date',
                'disposal_method' => 'required|in:Cremation,Burial',
            ],
            default => [],
        };

        return (new Validator($payload))->rules($rules + $typeRules);
    }

    private function normalizedValidationData(array $payload): array
    {
        foreach (['record_date', 'time_of_death'] as $field) {
            if (($payload[$field] ?? '') !== '') {
                $payload[$field] = $this->normalizeDateTime((string) $payload[$field]);
            }
        }

        foreach (['next_due_date', 'follow_up_date', 'start_date', 'end_date'] as $field) {
            if (($payload[$field] ?? '') !== '') {
                $payload[$field] = substr(str_replace('T', ' ', (string) $payload[$field]), 0, 10);
            }
        }

        return $payload;
    }

    private function normalizeDateTime(string $value): string
    {
        $value = str_replace('T', ' ', trim($value));

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
            return $value . ' 00:00:00';
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $value) === 1) {
            return $value . ':00';
        }

        return $value;
    }

    private function procedureTypes(): array
    {
        return [
            'vaccination' => 'Vaccination',
            'surgery' => 'Surgery',
            'examination' => 'Examination',
            'treatment' => 'Treatment',
            'deworming' => 'Deworming',
            'euthanasia' => 'Euthanasia',
        ];
    }

    private function allFormConfigs(): array
    {
        $configs = [];

        foreach (array_keys($this->procedureTypes()) as $type) {
            $configs[$type] = $this->medical->formConfig($type);
        }

        return $configs;
    }
}

<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\Concerns\InteractsWithApi;
use App\Controllers\Concerns\RendersViews;
use App\Core\Request;
use App\Core\Response;
use App\Middleware\CsrfMiddleware;
use App\Services\MedicalService;
use App\Support\Pagination;
use App\Support\Validation\MedicalInputValidator;
use RuntimeException;

class MedicalController
{
    use InteractsWithApi;
    use RendersViews;

    public function __construct(
        private readonly MedicalService $medical,
        private readonly MedicalInputValidator $validator
    ) {
    }

    public function index(Request $request): Response
    {
        return $this->renderAppView('medical.index', [
            'title' => 'Medical Records',
            'extraCss' => ['/assets/css/medical.css'],
            'extraJs' => ['/assets/js/medical.js'],
            'csrfToken' => CsrfMiddleware::token(),
            'animals' => $this->medical->animalOptions(),
            'practitioners' => $this->medical->practitioners(),
            'procedureTypes' => $this->procedureTypes(),
        ]);
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

        return $this->renderAppView('medical.create', [
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
        ]);
    }

    public function show(Request $request, string $id): Response
    {
        try {
            $record = $this->medical->get((int) $id);
        } catch (RuntimeException) {
            return Response::redirect('/medical');
        }

        return $this->renderAppView('medical.show', [
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
        ]);
    }

    public function list(Request $request): Response
    {
        $page = Pagination::page($request->query('page'));
        $perPage = Pagination::perPage($request->query('per_page'), 20);
        $result = $this->medical->list($request->query(), $page, $perPage);

        return $this->paginatedSuccess($result, $page, $perPage, 'Medical records retrieved successfully.');
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

        $payload = $this->validator->normalizePayload($request->body() + [
            'animal_id' => $current['animal_id'],
        ]);
        $validator = $this->validator->validateForType(
            $payload,
            (string) $current['procedure_type'],
            $request->file('lab_attachments'),
            false
        );

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $authUserId = $this->currentUserId($request);

        try {
            $record = $this->medical->update((int) $id, $payload, $authUserId, $request);
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
        try {
            $this->medical->delete((int) $id, $this->currentUserId($request), $request);
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
        $payload = $this->validator->normalizePayload($request->body());
        $validator = $this->validator->validateForType($payload, $type, $request->file('lab_attachments'), true);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $authUserId = $this->currentUserId($request);

        try {
            $record = $this->medical->create($type, $payload, $authUserId, $request);
        } catch (RuntimeException $exception) {
            return Response::error(409, 'MEDICAL_CREATE_BLOCKED', $exception->getMessage());
        }

        return Response::success([
            'record' => $record,
            'redirect' => '/medical/' . $record['id'],
        ], 'Medical record created successfully.');
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

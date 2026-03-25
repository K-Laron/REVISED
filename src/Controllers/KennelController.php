<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Helpers\Validator;
use App\Middleware\CsrfMiddleware;
use App\Services\KennelService;
use ReflectionClass;
use RuntimeException;

class KennelController
{
    private KennelService $kennels;

    public function __construct()
    {
        $this->kennels = new KennelService();
    }

    public function index(Request $request): Response
    {
        return Response::html(View::render('kennels.index', [
            'title' => 'Kennel Management',
            'extraCss' => ['/assets/css/kennels.css'],
            'extraJs' => ['/assets/js/kennels.js'],
            'csrfToken' => CsrfMiddleware::token(),
            'filters' => $request->query(),
            'assignableAnimals' => $this->kennels->assignableAnimals(),
            'existingKennelCodes' => $this->kennels->existingKennelCodes(),
            'zones' => $this->kennels->zones(),
        ], 'layouts.app'));
    }

    public function list(Request $request): Response
    {
        $items = $this->kennels->list($request->query());

        return Response::success($items, 'Kennels retrieved successfully.');
    }

    public function stats(Request $request): Response
    {
        return Response::success($this->kennels->stats(), 'Kennel stats retrieved successfully.');
    }

    public function store(Request $request): Response
    {
        $data = $request->body();

        if (trim((string) ($data['kennel_code'] ?? '')) === '') {
            $data['kennel_code'] = $this->kennels->generateKennelCode($data);
        }

        $validator = (new Validator($data))->rules($this->kennelRules());
        $this->validateUniqueCode($validator, $data);

        if ($validator->fails()) {
            return Response::error(422, 'VALIDATION_ERROR', 'The given data was invalid.', $validator->errors());
        }

        $authUser = $request->attribute('auth_user');

        try {
            $kennel = $this->kennels->create($data, (int) $authUser['id'], $request);
        } catch (\Throwable $exception) {
            return Response::error(500, 'SERVER_ERROR', $exception->getMessage());
        }

        return Response::success($kennel, 'Kennel created successfully.');
    }

    public function update(Request $request, string $id): Response
    {
        $data = $request->body();
        $validator = (new Validator($data))->rules($this->kennelRules());
        $this->validateUniqueCode($validator, $data, (int) $id);

        if ($validator->fails()) {
            return Response::error(422, 'VALIDATION_ERROR', 'The given data was invalid.', $validator->errors());
        }

        $authUser = $request->attribute('auth_user');

        try {
            $kennel = $this->kennels->update((int) $id, $data, (int) $authUser['id'], $request);
        } catch (RuntimeException $exception) {
            return Response::error(404, 'NOT_FOUND', $exception->getMessage());
        } catch (\Throwable $exception) {
            return Response::error(500, 'SERVER_ERROR', $exception->getMessage());
        }

        return Response::success($kennel, 'Kennel updated successfully.');
    }

    public function destroy(Request $request, string $id): Response
    {
        $authUser = $request->attribute('auth_user');

        try {
            $this->kennels->delete((int) $id, (int) $authUser['id'], $request);
        } catch (RuntimeException $exception) {
            return Response::error(409, 'KENNEL_DELETE_BLOCKED', $exception->getMessage());
        }

        return Response::success([], 'Kennel deleted successfully.');
    }

    public function assignAnimal(Request $request, string $id): Response
    {
        $validator = (new Validator($request->body()))->rules([
            'animal_id' => 'required|integer|exists:animals,id',
            'transfer_reason' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return Response::error(422, 'VALIDATION_ERROR', 'The given data was invalid.', $validator->errors());
        }

        $authUser = $request->attribute('auth_user');

        try {
            $kennel = $this->kennels->assignAnimal(
                (int) $id,
                (int) $request->body('animal_id'),
                $request->body('transfer_reason'),
                (int) $authUser['id'],
                $request
            );
        } catch (RuntimeException $exception) {
            return Response::error(409, 'KENNEL_ASSIGNMENT_BLOCKED', $exception->getMessage());
        }

        return Response::success($kennel, 'Animal assigned successfully.');
    }

    public function releaseAnimal(Request $request, string $id): Response
    {
        $validator = (new Validator($request->body()))->rules([
            'transfer_reason' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return Response::error(422, 'VALIDATION_ERROR', 'The given data was invalid.', $validator->errors());
        }

        $authUser = $request->attribute('auth_user');

        try {
            $kennel = $this->kennels->releaseAnimal((int) $id, $request->body('transfer_reason'), (int) $authUser['id'], $request);
        } catch (RuntimeException $exception) {
            return Response::error(409, 'KENNEL_RELEASE_BLOCKED', $exception->getMessage());
        }

        return Response::success($kennel, 'Animal released successfully.');
    }

    public function history(Request $request, string $id): Response
    {
        try {
            $history = $this->kennels->history((int) $id);
        } catch (RuntimeException $exception) {
            return Response::error(404, 'NOT_FOUND', $exception->getMessage());
        }

        return Response::success($history, 'Kennel history retrieved successfully.');
    }

    public function logMaintenance(Request $request, string $id): Response
    {
        $validator = (new Validator($request->body()))->rules([
            'maintenance_type' => 'required|string|max:50',
            'description' => 'nullable|string|max:2000',
            'scheduled_date' => 'nullable|string',
            'completed_at' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return Response::error(422, 'VALIDATION_ERROR', 'The given data was invalid.', $validator->errors());
        }

        $authUser = $request->attribute('auth_user');

        try {
            $kennel = $this->kennels->logMaintenance((int) $id, $request->body(), (int) $authUser['id'], $request);
        } catch (RuntimeException $exception) {
            return Response::error(409, 'KENNEL_MAINTENANCE_BLOCKED', $exception->getMessage());
        }

        return Response::success($kennel, 'Maintenance log saved successfully.');
    }

    public function maintenanceHistory(Request $request, string $id): Response
    {
        try {
            $logs = $this->kennels->maintenanceHistory((int) $id);
        } catch (RuntimeException $exception) {
            return Response::error(404, 'NOT_FOUND', $exception->getMessage());
        }

        return Response::success($logs, 'Maintenance history retrieved successfully.');
    }

    private function kennelRules(): array
    {
        return [
            'kennel_code' => 'required|string|max:20',
            'zone' => 'required|string|max:50',
            'row_number' => 'nullable|string|max:10',
            'size_category' => 'required|in:Small,Medium,Large,Extra Large',
            'type' => 'required|in:Indoor,Outdoor',
            'allowed_species' => 'required|in:Dog,Cat,Any',
            'max_occupants' => 'required|integer|between:1,20',
            'status' => 'required|in:Available,Occupied,Maintenance,Quarantine',
            'notes' => 'nullable|string|max:2000',
        ];
    }

    private function validateUniqueCode(Validator $validator, array $data, ?int $ignoreId = null): void
    {
        $service = new KennelService();

        try {
            $items = $service->list();
        } catch (\Throwable) {
            return;
        }

        $code = trim((string) ($data['kennel_code'] ?? ''));
        if ($code === '') {
            return;
        }

        foreach ($items as $item) {
            if ($item['kennel_code'] !== $code) {
                continue;
            }

            if ($ignoreId !== null && (int) $item['id'] === $ignoreId) {
                return;
            }

            $errors = $validator->errors();
            $errors['kennel_code'][] = 'Kennel code has already been taken.';

            $reflection = new ReflectionClass($validator);
            $property = $reflection->getProperty('errors');
            $property->setAccessible(true);
            $property->setValue($validator, $errors);
            return;
        }
    }
}

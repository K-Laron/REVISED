<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Helpers\Validator;
use App\Middleware\CsrfMiddleware;
use App\Services\AnimalService;
use RuntimeException;

class AnimalController
{
    private AnimalService $animals;

    public function __construct()
    {
        $this->animals = new AnimalService();
    }

    public function index(Request $request): Response
    {
        return Response::html(View::render('animals.index', [
            'title' => 'Animals',
            'extraCss' => ['/assets/css/animals.css'],
            'extraJs' => ['https://unpkg.com/html5-qrcode', '/assets/js/animals.js'],
            'filters' => $request->query(),
        ], 'layouts.app'));
    }

    public function create(Request $request): Response
    {
        return Response::html(View::render('animals.create', [
            'title' => 'New Animal Intake',
            'extraCss' => ['/assets/css/animals.css'],
            'extraJs' => ['https://unpkg.com/html5-qrcode', '/assets/js/animals.js'],
            'csrfToken' => CsrfMiddleware::token(),
            'breeds' => $this->animals->breeds(),
            'kennels' => $this->animals->availableKennels(),
        ], 'layouts.app'));
    }

    public function show(Request $request, string $id): Response
    {
        try {
            $animal = $this->animals->get($id);
        } catch (RuntimeException) {
            return Response::redirect('/animals');
        }

        return Response::html(View::render('animals.show', [
            'title' => $animal['animal_id'] . ' · Animal Detail',
            'extraCss' => ['/assets/css/animals.css'],
            'extraJs' => ['https://unpkg.com/html5-qrcode', '/assets/js/animals.js'],
            'animal' => $animal,
            'csrfToken' => CsrfMiddleware::token(),
        ], 'layouts.app'));
    }

    public function edit(Request $request, string $id): Response
    {
        try {
            $animal = $this->animals->get($id);
        } catch (RuntimeException) {
            return Response::redirect('/animals');
        }

        return Response::html(View::render('animals.edit', [
            'title' => $animal['animal_id'] . ' · Edit Animal',
            'extraCss' => ['/assets/css/animals.css'],
            'extraJs' => ['https://unpkg.com/html5-qrcode', '/assets/js/animals.js'],
            'animal' => $animal,
            'csrfToken' => CsrfMiddleware::token(),
            'breeds' => $this->animals->breeds(),
            'kennels' => $this->animals->availableKennels((int) ($animal['current_kennel']['id'] ?? 0) ?: null),
        ], 'layouts.app'));
    }

    public function list(Request $request): Response
    {
        $page = max(1, (int) $request->query('page', 1));
        $perPage = max(1, min(100, (int) $request->query('per_page', 20)));
        $result = $this->animals->list($request->query(), $page, $perPage);

        return Response::success(
            $result['items'],
            'Animals retrieved successfully.',
            [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $result['total'],
                'total_pages' => (int) ceil(max(1, $result['total']) / $perPage),
            ]
        );
    }

    public function store(Request $request): Response
    {
        $validator = $this->validateAnimalPayload($request, true);
        if ($validator->fails()) {
            return Response::error(422, 'VALIDATION_ERROR', 'The given data was invalid.', $validator->errors());
        }

        $authUser = $request->attribute('auth_user');

        try {
            $created = $this->animals->create($request->body(), $request->file(), (int) $authUser['id'], $request);
        } catch (\Throwable $exception) {
            return Response::error(500, 'SERVER_ERROR', $exception->getMessage());
        }

        return Response::success([
            'animal' => $created['animal'],
            'qr' => $created['qr'],
            'redirect' => '/animals/' . $created['animal']['id'],
        ], 'Animal created successfully.');
    }

    public function get(Request $request, string $id): Response
    {
        try {
            $animal = $this->animals->get($id);
        } catch (RuntimeException) {
            return Response::error(404, 'NOT_FOUND', 'Animal not found.');
        }

        return Response::success($animal, 'Animal retrieved successfully.');
    }

    public function update(Request $request, string $id): Response
    {
        $validator = $this->validateAnimalPayload($request, false);
        if ($validator->fails()) {
            return Response::error(422, 'VALIDATION_ERROR', 'The given data was invalid.', $validator->errors());
        }

        $authUser = $request->attribute('auth_user');

        try {
            $animal = $this->animals->update((int) $id, $request->body(), (int) $authUser['id'], $request);
        } catch (RuntimeException) {
            return Response::error(404, 'NOT_FOUND', 'Animal not found.');
        } catch (\Throwable $exception) {
            return Response::error(500, 'SERVER_ERROR', $exception->getMessage());
        }

        return Response::success([
            'animal' => $animal,
            'redirect' => '/animals/' . $animal['id'],
        ], 'Animal updated successfully.');
    }

    public function destroy(Request $request, string $id): Response
    {
        $authUser = $request->attribute('auth_user');
        try {
            $this->animals->delete((int) $id, (int) $authUser['id'], $request);
        } catch (RuntimeException) {
            return Response::error(404, 'NOT_FOUND', 'Animal not found.');
        }

        return Response::success([], 'Animal deleted successfully.');
    }

    public function restore(Request $request, string $id): Response
    {
        $authUser = $request->attribute('auth_user');
        $this->animals->restore((int) $id, (int) $authUser['id'], $request);

        return Response::success([], 'Animal restored successfully.');
    }

    public function updateStatus(Request $request, string $id): Response
    {
        $validator = (new Validator($request->body()))->rules([
            'status' => 'required|in:Available,Under Medical Care,In Adoption Process,Adopted,Deceased,Transferred,Quarantine',
            'status_reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return Response::error(422, 'VALIDATION_ERROR', 'The given data was invalid.', $validator->errors());
        }

        $authUser = $request->attribute('auth_user');
        try {
            $animal = $this->animals->updateStatus((int) $id, (string) $request->body('status'), $request->body('status_reason'), (int) $authUser['id'], $request);
        } catch (RuntimeException) {
            return Response::error(404, 'NOT_FOUND', 'Animal not found.');
        }

        return Response::success($animal, 'Animal status updated successfully.');
    }

    public function uploadPhoto(Request $request, string $id): Response
    {
        $validator = new Validator([]);
        $this->validatePhotos($validator, $request->file('photos'));
        if ($validator->fails()) {
            return Response::error(422, 'VALIDATION_ERROR', 'The given data was invalid.', $validator->errors());
        }

        $authUser = $request->attribute('auth_user');

        try {
            $photos = $this->animals->uploadPhoto((int) $id, $request->file('photos'), (int) $authUser['id'], $request);
        } catch (RuntimeException) {
            return Response::error(404, 'NOT_FOUND', 'Animal not found.');
        } catch (\Throwable $exception) {
            return Response::error(500, 'SERVER_ERROR', $exception->getMessage());
        }

        return Response::success($photos, 'Photos uploaded successfully.');
    }

    public function deletePhoto(Request $request, string $id, string $photoId): Response
    {
        $authUser = $request->attribute('auth_user');

        try {
            $this->animals->deletePhoto((int) $id, (int) $photoId, (int) $authUser['id'], $request);
        } catch (RuntimeException) {
            return Response::error(404, 'NOT_FOUND', 'Photo not found.');
        }

        return Response::success([], 'Photo deleted successfully.');
    }

    public function timeline(Request $request, string $id): Response
    {
        try {
            $timeline = $this->animals->timeline((int) $id);
        } catch (RuntimeException) {
            return Response::error(404, 'NOT_FOUND', 'Animal not found.');
        }

        return Response::success($timeline, 'Animal timeline retrieved successfully.');
    }

    private function validateAnimalPayload(Request $request, bool $creating): Validator
    {
        $rules = [
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
            'intake_type' => 'required|in:Stray,Owner Surrender,Confiscated,Transfer,Born in Shelter',
            'intake_date' => 'required|string',
            'location_found' => 'nullable|string|max:500',
            'brought_by_name' => 'nullable|string|max:200',
            'brought_by_contact' => 'nullable|phone_ph',
            'brought_by_address' => 'nullable|string|max:500',
            'surrender_reason' => 'nullable|string|max:1000',
            'condition_at_intake' => 'required|in:Healthy,Injured,Sick,Malnourished,Aggressive',
            'temperament' => 'required|in:Friendly,Shy,Aggressive,Unknown',
            'kennel_id' => 'nullable|integer|exists:kennels,id',
        ];

        $validator = (new Validator($request->body()))->rules($rules);

        if (($request->body('intake_type') === 'Stray') && trim((string) $request->body('location_found', '')) === '') {
            $this->addManualError($validator, 'location_found', 'Location found is required for stray intake.');
        }

        if (($request->body('intake_type') === 'Owner Surrender') && trim((string) $request->body('surrender_reason', '')) === '') {
            $this->addManualError($validator, 'surrender_reason', 'Surrender reason is required for owner surrender.');
        }

        $this->validatePhotos($validator, $request->file('photos'));

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
            $this->addManualError($validator, 'photos', 'You may upload at most 5 photos.');
        }

        foreach ($names as $index => $name) {
            if (!$name) {
                continue;
            }

            $extension = strtolower(pathinfo((string) $name, PATHINFO_EXTENSION));
            if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                $this->addManualError($validator, 'photos', 'Photos must be JPG, JPEG, PNG, or WebP.');
                break;
            }

            if ((int) ($sizes[$index] ?? 0) > (5 * 1024 * 1024)) {
                $this->addManualError($validator, 'photos', 'Each photo must not exceed 5MB.');
                break;
            }
        }
    }

    private function addManualError(Validator $validator, string $field, string $message): void
    {
        $errors = $validator->errors();
        $errors[$field][] = $message;

        $reflection = new \ReflectionClass($validator);
        $property = $reflection->getProperty('errors');
        $property->setAccessible(true);
        $property->setValue($validator, $errors);
    }
}

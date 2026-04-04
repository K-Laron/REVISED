<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\Concerns\InteractsWithApi;
use App\Controllers\Concerns\RendersViews;
use App\Core\Request;
use App\Core\Response;
use App\Middleware\CsrfMiddleware;
use App\Services\AnimalService;
use App\Support\Pagination;
use App\Support\Validation\AnimalInputValidator;
use InvalidArgumentException;
use RuntimeException;

class AnimalController
{
    use InteractsWithApi;
    use RendersViews;

    private AnimalService $animals;
    private AnimalInputValidator $validator;

    public function __construct()
    {
        $this->animals = new AnimalService();
        $this->validator = new AnimalInputValidator();
    }

    public function index(Request $request): Response
    {
        return $this->renderAppView('animals.index', [
            'title' => 'Animals',
            'extraCss' => ['/assets/css/animals.css'],
            'extraJs' => ['https://unpkg.com/html5-qrcode', '/assets/js/animals.js'],
            'filters' => $request->query(),
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->renderAppView('animals.create', [
            'title' => 'New Animal Intake',
            'extraCss' => ['/assets/css/animals.css'],
            'extraJs' => ['https://unpkg.com/html5-qrcode', '/assets/js/animals.js'],
            'csrfToken' => CsrfMiddleware::token(),
            'breeds' => $this->animals->breeds(),
            'kennels' => $this->animals->availableKennels(),
        ]);
    }

    public function show(Request $request, string $id): Response
    {
        try {
            $animal = $this->animals->get($id);
        } catch (RuntimeException) {
            return Response::redirect('/animals');
        }

        return $this->renderAppView('animals.show', [
            'title' => $animal['animal_id'] . ' · Animal Detail',
            'extraCss' => ['/assets/css/animals.css'],
            'extraJs' => ['https://unpkg.com/html5-qrcode', '/assets/js/animals.js'],
            'animal' => $animal,
            'csrfToken' => CsrfMiddleware::token(),
        ]);
    }

    public function edit(Request $request, string $id): Response
    {
        try {
            $animal = $this->animals->get($id);
        } catch (RuntimeException) {
            return Response::redirect('/animals');
        }

        return $this->renderAppView('animals.edit', [
            'title' => $animal['animal_id'] . ' · Edit Animal',
            'extraCss' => ['/assets/css/animals.css'],
            'extraJs' => ['https://unpkg.com/html5-qrcode', '/assets/js/animals.js'],
            'animal' => $animal,
            'csrfToken' => CsrfMiddleware::token(),
            'breeds' => $this->animals->breeds(),
            'kennels' => $this->animals->availableKennels((int) ($animal['current_kennel']['id'] ?? 0) ?: null),
        ]);
    }

    public function list(Request $request): Response
    {
        $page = Pagination::page($request->query('page'));
        $perPage = Pagination::perPage($request->query('per_page'), 20);
        $result = $this->animals->list($request->query(), $page, $perPage);

        return $this->paginatedSuccess($result, $page, $perPage, 'Animals retrieved successfully.');
    }

    public function store(Request $request): Response
    {
        $validator = $this->validator->validateAnimal($request->body(), $request->file('photos'));
        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $authUserId = $this->currentUserId($request);

        try {
            $created = $this->animals->create($request->body(), $request->file(), $authUserId, $request);
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
        $validator = $this->validator->validateAnimal($request->body(), $request->file('photos'));
        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $authUserId = $this->currentUserId($request);

        try {
            $animal = $this->animals->update((int) $id, $request->body(), $authUserId, $request);
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
        try {
            $this->animals->delete((int) $id, $this->currentUserId($request), $request);
        } catch (RuntimeException) {
            return Response::error(404, 'NOT_FOUND', 'Animal not found.');
        }

        return Response::success([], 'Animal deleted successfully.');
    }

    public function restore(Request $request, string $id): Response
    {
        $this->animals->restore((int) $id, $this->currentUserId($request), $request);

        return Response::success([], 'Animal restored successfully.');
    }

    public function updateStatus(Request $request, string $id): Response
    {
        $validator = $this->validator->validateStatus($request->body());

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $authUserId = $this->currentUserId($request);
        try {
            $animal = $this->animals->updateStatus((int) $id, (string) $request->body('status'), $request->body('status_reason'), $authUserId, $request);
        } catch (RuntimeException) {
            return Response::error(404, 'NOT_FOUND', 'Animal not found.');
        }

        return Response::success($animal, 'Animal status updated successfully.');
    }

    public function uploadPhoto(Request $request, string $id): Response
    {
        $validator = $this->validator->validatePhotoUpload($request->file('photos'));
        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $authUserId = $this->currentUserId($request);

        try {
            $photos = $this->animals->uploadPhoto((int) $id, $request->file('photos'), $authUserId, $request);
        } catch (RuntimeException) {
            return Response::error(404, 'NOT_FOUND', 'Animal not found.');
        } catch (\Throwable $exception) {
            return Response::error(500, 'SERVER_ERROR', $exception->getMessage());
        }

        return Response::success($photos, 'Photos uploaded successfully.');
    }

    public function reorderPhotos(Request $request, string $id): Response
    {
        $validator = $this->validator->validatePhotoReorder($request->body());
        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $authUserId = $this->currentUserId($request);

        try {
            $photos = $this->animals->reorderPhotos((int) $id, $request->body('photo_ids', []), $authUserId, $request);
        } catch (RuntimeException) {
            return Response::error(404, 'NOT_FOUND', 'Animal not found.');
        } catch (InvalidArgumentException $exception) {
            return Response::error(422, 'VALIDATION_ERROR', $exception->getMessage());
        } catch (\Throwable $exception) {
            return Response::error(500, 'SERVER_ERROR', $exception->getMessage());
        }

        return Response::success($photos, 'Photos reordered successfully.');
    }

    public function deletePhoto(Request $request, string $id, string $photoId): Response
    {
        try {
            $this->animals->deletePhoto((int) $id, (int) $photoId, $this->currentUserId($request), $request);
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
}

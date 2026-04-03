<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\Concerns\InteractsWithApi;
use App\Controllers\Concerns\RendersViews;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Middleware\CsrfMiddleware;
use App\Services\InventoryService;
use App\Support\Pagination;
use App\Support\Validation\InventoryInputValidator;
use RuntimeException;

class InventoryController
{
    use InteractsWithApi;
    use RendersViews;

    private InventoryService $inventory;
    private InventoryInputValidator $validator;

    public function __construct()
    {
        $this->inventory = new InventoryService();
        $this->validator = new InventoryInputValidator();
    }

    public function index(Request $request): Response
    {
        return $this->renderAppView('inventory.index', [
            'title' => 'Inventory Management',
            'extraCss' => ['/assets/css/inventory.css'],
            'extraJs' => [
                '/assets/js/inventory/inventory-formatters.js',
                '/assets/js/inventory/inventory-render.js',
                '/assets/js/inventory.js',
            ],
            'csrfToken' => CsrfMiddleware::token(),
            'categories' => $this->inventory->categories(),
        ]);
    }

    public function show(Request $request, string $id): Response
    {
        try {
            $item = $this->inventory->get((int) $id);
        } catch (RuntimeException $exception) {
            return Response::html(View::render('errors.404', [
                'title' => 'Inventory Item Not Found',
                'message' => $exception->getMessage(),
            ]), 404);
        }

        return $this->renderAppView('inventory.show', [
            'title' => (string) ($item['name'] ?? 'Inventory Item'),
            'extraCss' => ['/assets/css/inventory.css'],
            'item' => $item,
        ]);
    }

    public function list(Request $request): Response
    {
        $page = Pagination::page($request->query('page'));
        $perPage = Pagination::perPage($request->query('per_page'), 20);
        $result = $this->inventory->list($request->query(), $page, $perPage);

        return $this->paginatedSuccess($result, $page, $perPage, 'Inventory items retrieved successfully.');
    }

    public function get(Request $request, string $id): Response
    {
        try {
            $item = $this->inventory->get((int) $id);
        } catch (RuntimeException $exception) {
            return Response::error(404, 'NOT_FOUND', $exception->getMessage());
        }

        return Response::success($item, 'Inventory item retrieved successfully.');
    }

    public function store(Request $request): Response
    {
        $validator = $this->validator->validateCreateItem($request->body());

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $authUserId = $this->currentUserId($request);

        try {
            $item = $this->inventory->create($request->body(), $authUserId, $request);
        } catch (RuntimeException $exception) {
            return Response::error(409, 'INVENTORY_CREATE_BLOCKED', $exception->getMessage());
        }

        return Response::success($item, 'Inventory item created successfully.');
    }

    public function update(Request $request, string $id): Response
    {
        $validator = $this->validator->validateUpdateItem($request->body());

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $authUserId = $this->currentUserId($request);

        try {
            $item = $this->inventory->update((int) $id, $request->body(), $authUserId, $request);
        } catch (RuntimeException $exception) {
            return Response::error(409, 'INVENTORY_UPDATE_BLOCKED', $exception->getMessage());
        }

        return Response::success($item, 'Inventory item updated successfully.');
    }

    public function destroy(Request $request, string $id): Response
    {
        try {
            $this->inventory->delete((int) $id, $this->currentUserId($request), $request);
        } catch (RuntimeException $exception) {
            return Response::error(404, 'NOT_FOUND', $exception->getMessage());
        }

        return Response::success([], 'Inventory item deleted successfully.');
    }

    public function stockIn(Request $request, string $id): Response
    {
        return $this->handleStockChange($request, (int) $id, 'stockIn');
    }

    public function stockOut(Request $request, string $id): Response
    {
        return $this->handleStockChange($request, (int) $id, 'stockOut');
    }

    public function adjust(Request $request, string $id): Response
    {
        $validator = $this->validator->validateAdjustment($request->body());

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $authUserId = $this->currentUserId($request);

        try {
            $item = $this->inventory->adjust((int) $id, $request->body(), $authUserId, $request);
        } catch (RuntimeException $exception) {
            return Response::error(409, 'INVENTORY_ADJUST_BLOCKED', $exception->getMessage());
        }

        return Response::success($item, 'Inventory count adjusted successfully.');
    }

    public function transactions(Request $request, string $id): Response
    {
        try {
            $transactions = $this->inventory->transactions((int) $id);
        } catch (RuntimeException $exception) {
            return Response::error(404, 'NOT_FOUND', $exception->getMessage());
        }

        return Response::success($transactions, 'Inventory transactions retrieved successfully.');
    }

    public function categories(Request $request): Response
    {
        return Response::success($this->inventory->categories(), 'Inventory categories retrieved successfully.');
    }

    public function storeCategory(Request $request): Response
    {
        $validator = $this->validator->validateCategory($request->body());

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $authUserId = $this->currentUserId($request);

        try {
            $category = $this->inventory->storeCategory((string) $request->body('name'), $request->body('description'), $authUserId, $request);
        } catch (RuntimeException $exception) {
            return Response::error(409, 'CATEGORY_CREATE_BLOCKED', $exception->getMessage());
        }

        return Response::success($category, 'Inventory category created successfully.');
    }

    public function alerts(Request $request): Response
    {
        return Response::success($this->inventory->alerts(), 'Inventory alerts retrieved successfully.');
    }

    public function stats(Request $request): Response
    {
        return Response::success($this->inventory->stats(), 'Inventory stats retrieved successfully.');
    }

    private function handleStockChange(Request $request, int $itemId, string $method): Response
    {
        $validator = $this->validator->validateStockChange($request->body());

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $authUserId = $this->currentUserId($request);

        try {
            $item = $this->inventory->{$method}($itemId, $request->body(), $authUserId, $request);
        } catch (RuntimeException $exception) {
            return Response::error(409, 'INVENTORY_STOCK_CHANGE_BLOCKED', $exception->getMessage());
        }

        return Response::success($item, 'Inventory stock updated successfully.');
    }
}

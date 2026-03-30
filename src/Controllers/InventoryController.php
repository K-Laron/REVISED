<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\Concerns\InteractsWithApi;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Helpers\Validator;
use App\Middleware\CsrfMiddleware;
use App\Services\InventoryService;
use App\Support\Pagination;
use RuntimeException;

class InventoryController
{
    use InteractsWithApi;

    private InventoryService $inventory;

    public function __construct()
    {
        $this->inventory = new InventoryService();
    }

    public function index(Request $request): Response
    {
        return Response::html(View::render('inventory.index', [
            'title' => 'Inventory Management',
            'extraCss' => ['/assets/css/inventory.css'],
            'extraJs' => [
                '/assets/js/inventory/inventory-formatters.js',
                '/assets/js/inventory/inventory-render.js',
                '/assets/js/inventory.js',
            ],
            'csrfToken' => CsrfMiddleware::token(),
            'categories' => $this->inventory->categories(),
        ], 'layouts.app'));
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

        return Response::html(View::render('inventory.show', [
            'title' => (string) ($item['name'] ?? 'Inventory Item'),
            'extraCss' => ['/assets/css/inventory.css'],
            'item' => $item,
        ], 'layouts.app'));
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
        $validator = (new Validator($request->body()))->rules([
            'sku' => 'required|string|max:50',
            'name' => 'required|string|max:200',
            'category_id' => 'required|integer|exists:inventory_categories,id',
            'unit_of_measure' => 'required|in:pcs,ml,mg,kg,box,pack,bottle,vial,tube,roll',
            'cost_per_unit' => 'nullable|numeric|between:0,999999',
            'supplier_name' => 'nullable|string|max:200',
            'supplier_contact' => 'nullable|string|max:100',
            'reorder_level' => 'required|integer|between:0,10000',
            'quantity_on_hand' => 'required|integer|between:0,99999',
            'storage_location' => 'nullable|string|max:100',
            'expiry_date' => 'nullable|date',
        ]);

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
        $validator = (new Validator($request->body()))->rules([
            'sku' => 'required|string|max:50',
            'name' => 'required|string|max:200',
            'category_id' => 'required|integer|exists:inventory_categories,id',
            'unit_of_measure' => 'required|in:pcs,ml,mg,kg,box,pack,bottle,vial,tube,roll',
            'cost_per_unit' => 'nullable|numeric|between:0,999999',
            'supplier_name' => 'nullable|string|max:200',
            'supplier_contact' => 'nullable|string|max:100',
            'reorder_level' => 'required|integer|between:0,10000',
            'storage_location' => 'nullable|string|max:100',
            'expiry_date' => 'nullable|date',
        ]);

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
        $validator = (new Validator($request->body()))->rules([
            'quantity' => 'required|integer|between:0,100000',
            'reason' => 'required|in:purchase,donation,return,usage,dispensed,wastage,transfer,count_correction',
            'expiry_date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
        ]);

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
        $validator = (new Validator($request->body()))->rules([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:2000',
        ]);

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
        $validator = (new Validator($request->body()))->rules([
            'quantity' => 'required|integer|between:1,10000',
            'reason' => 'required|in:purchase,donation,return,usage,dispensed,wastage,transfer,count_correction',
            'batch_lot_number' => 'nullable|string|max:50',
            'expiry_date' => 'nullable|date',
            'source_supplier' => 'nullable|string|max:200',
            'notes' => 'nullable|string|max:500',
        ]);

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

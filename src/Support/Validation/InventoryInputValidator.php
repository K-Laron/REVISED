<?php

declare(strict_types=1);

namespace App\Support\Validation;

use App\Helpers\Validator;

final class InventoryInputValidator
{
    public function validateCreateItem(array $data): Validator
    {
        return (new Validator($data))->rules($this->itemRules(true));
    }

    public function validateUpdateItem(array $data): Validator
    {
        return (new Validator($data))->rules($this->itemRules(false));
    }

    public function validateStockChange(array $data): Validator
    {
        return (new Validator($data))->rules([
            'quantity' => 'required|integer|between:1,10000',
            'reason' => 'required|in:purchase,donation,return,usage,dispensed,wastage,transfer,count_correction',
            'batch_lot_number' => 'nullable|string|max:50',
            'expiry_date' => 'nullable|date',
            'source_supplier' => 'nullable|string|max:200',
            'notes' => 'nullable|string|max:500',
        ]);
    }

    public function validateAdjustment(array $data): Validator
    {
        return (new Validator($data))->rules([
            'quantity' => 'required|integer|between:0,100000',
            'reason' => 'required|in:purchase,donation,return,usage,dispensed,wastage,transfer,count_correction',
            'expiry_date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
        ]);
    }

    public function validateCategory(array $data): Validator
    {
        return (new Validator($data))->rules([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:2000',
        ]);
    }

    private function itemRules(bool $creating): array
    {
        $rules = [
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
        ];

        if ($creating) {
            $rules['quantity_on_hand'] = 'required|integer|between:0,99999';
        }

        return $rules;
    }
}

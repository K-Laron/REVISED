<?php

declare(strict_types=1);

namespace Tests\Support\Validation;

use App\Support\Validation\InventoryInputValidator;
use PHPUnit\Framework\TestCase;

final class InventoryInputValidatorTest extends TestCase
{
    public function testCreateValidationRequiresInitialQuantityOnHand(): void
    {
        $validator = (new InventoryInputValidator())->validateCreateItem([
            'sku' => 'INV-001',
            'name' => 'Rabies Vaccine',
            'category_id' => '',
            'unit_of_measure' => 'vial',
            'reorder_level' => '5',
        ]);

        self::assertTrue($validator->fails());
        self::assertSame('Quantity on hand is required.', $validator->errors()['quantity_on_hand'][0]);
    }

    public function testUpdateValidationDoesNotRequireQuantityOnHand(): void
    {
        $validator = (new InventoryInputValidator())->validateUpdateItem([
            'sku' => 'INV-001',
            'name' => 'Rabies Vaccine',
            'category_id' => '',
            'unit_of_measure' => 'vial',
            'reorder_level' => '5',
        ]);

        self::assertArrayNotHasKey('quantity_on_hand', $validator->errors());
    }

    public function testStockChangeValidationRejectsZeroQuantity(): void
    {
        $validator = (new InventoryInputValidator())->validateStockChange([
            'quantity' => '0',
            'reason' => 'purchase',
        ]);

        self::assertTrue($validator->fails());
        self::assertSame('Quantity must be between 1 and 10000.', $validator->errors()['quantity'][0]);
    }
}

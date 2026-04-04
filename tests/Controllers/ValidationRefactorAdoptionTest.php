<?php

declare(strict_types=1);

namespace Tests\Controllers;

use PHPUnit\Framework\TestCase;

final class ValidationRefactorAdoptionTest extends TestCase
{
    public function testTargetControllersDelegateValidationToDedicatedValidatorClasses(): void
    {
        $root = dirname(__DIR__, 2);
        $animalController = (string) file_get_contents($root . '/src/Controllers/AnimalController.php');
        $inventoryController = (string) file_get_contents($root . '/src/Controllers/InventoryController.php');
        $medicalController = (string) file_get_contents($root . '/src/Controllers/MedicalController.php');

        self::assertStringContainsString('AnimalInputValidator', $animalController);
        self::assertStringContainsString('InventoryInputValidator', $inventoryController);
        self::assertStringContainsString('MedicalInputValidator', $medicalController);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Controllers;

use PHPUnit\Framework\TestCase;

final class RendersViewsAdoptionTest extends TestCase
{
    public function testBatchOneControllersRouteAppPagesThroughSharedRenderHelper(): void
    {
        $controllerPaths = [
            'C:\\Users\\TESS LARON\\Desktop\\REVISED\\src\\Controllers\\AnimalController.php',
            'C:\\Users\\TESS LARON\\Desktop\\REVISED\\src\\Controllers\\AdoptionController.php',
            'C:\\Users\\TESS LARON\\Desktop\\REVISED\\src\\Controllers\\BillingController.php',
            'C:\\Users\\TESS LARON\\Desktop\\REVISED\\src\\Controllers\\DashboardController.php',
            'C:\\Users\\TESS LARON\\Desktop\\REVISED\\src\\Controllers\\InventoryController.php',
            'C:\\Users\\TESS LARON\\Desktop\\REVISED\\src\\Controllers\\MedicalController.php',
            'C:\\Users\\TESS LARON\\Desktop\\REVISED\\src\\Controllers\\ReportController.php',
            'C:\\Users\\TESS LARON\\Desktop\\REVISED\\src\\Controllers\\UserController.php',
        ];

        foreach ($controllerPaths as $path) {
            $source = (string) file_get_contents($path);

            self::assertStringContainsString('use App\\Controllers\\Concerns\\RendersViews;', $source, $path);
            self::assertStringContainsString('use RendersViews;', $source, $path);
            self::assertStringContainsString('renderAppView(', $source, $path);
        }
    }
}

<?php

declare(strict_types=1);

namespace Tests\Controllers;

use PHPUnit\Framework\TestCase;

final class RendersViewsAdoptionTest extends TestCase
{
    public function testBatchOneControllersRouteAppPagesThroughSharedRenderHelper(): void
    {
        $root = dirname(__DIR__, 2);
        $controllerPaths = [
            $root . '/src/Controllers/AnimalController.php',
            $root . '/src/Controllers/AdoptionController.php',
            $root . '/src/Controllers/BillingController.php',
            $root . '/src/Controllers/DashboardController.php',
            $root . '/src/Controllers/InventoryController.php',
            $root . '/src/Controllers/MedicalController.php',
            $root . '/src/Controllers/ReportController.php',
            $root . '/src/Controllers/UserController.php',
        ];

        foreach ($controllerPaths as $path) {
            $source = (string) file_get_contents($path);

            self::assertStringContainsString('use App\\Controllers\\Concerns\\RendersViews;', $source, $path);
            self::assertStringContainsString('use RendersViews;', $source, $path);
            self::assertStringContainsString('renderAppView(', $source, $path);
        }
    }
}

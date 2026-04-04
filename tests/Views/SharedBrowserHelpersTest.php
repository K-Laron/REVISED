<?php

declare(strict_types=1);

namespace Tests\Views;

require_once __DIR__ . '/ViewSmokeTestCase.php';

final class SharedBrowserHelpersTest extends ViewSmokeTestCase
{
    public function testBothLayoutsLoadSharedBrowserHelperAsset(): void
    {
        $appHtml = $this->renderApp('dashboard.index');
        $publicHtml = $this->renderPublic('portal.landing', [
            'featuredAnimals' => $this->featuredAnimals(),
        ]);

        self::assertStringContainsString('/assets/js/core/app-api.js', $appHtml);
        self::assertStringContainsString('/assets/js/core/app-api.js', $publicHtml);
        self::assertStringContainsString('/assets/js/core/app-formatters.js', $appHtml);
        self::assertStringContainsString('/assets/js/core/app-formatters.js', $publicHtml);
    }

    public function testPageScriptsReferenceSharedBrowserHelpers(): void
    {
        $root = dirname(__DIR__, 2);
        $animalsScript = (string) file_get_contents($root . '/public/assets/js/animals.js');
        $adoptionsScript = (string) file_get_contents($root . '/public/assets/js/adoptions.js');
        $billingScript = (string) file_get_contents($root . '/public/assets/js/billing.js');
        $medicalScript = (string) file_get_contents($root . '/public/assets/js/medical.js');
        $usersScript = (string) file_get_contents($root . '/public/assets/js/users.js');
        $settingsScript = (string) file_get_contents($root . '/public/assets/js/settings.js');
        $portalScript = (string) file_get_contents($root . '/public/assets/js/portal.js');
        $billingFormatterScript = (string) file_get_contents($root . '/public/assets/js/billing.js');
        $medicalFormatterScript = (string) file_get_contents($root . '/public/assets/js/medical.js');
        $adoptionsFormatterScript = (string) file_get_contents($root . '/public/assets/js/adoptions.js');

        self::assertStringContainsString('window.CatarmanApi', $animalsScript);
        self::assertStringContainsString('window.CatarmanDom', $animalsScript);
        self::assertStringContainsString('window.CatarmanApi', $adoptionsScript);
        self::assertStringContainsString('window.CatarmanDom', $adoptionsScript);
        self::assertStringContainsString('window.CatarmanApi', $billingScript);
        self::assertStringContainsString('window.CatarmanDom', $billingScript);
        self::assertStringContainsString('window.CatarmanApi', $medicalScript);
        self::assertStringContainsString('window.CatarmanDom', $medicalScript);
        self::assertStringContainsString('window.CatarmanApi', $usersScript);
        self::assertStringContainsString('window.CatarmanApi', $settingsScript);
        self::assertStringContainsString('window.CatarmanApi', $portalScript);
        self::assertStringContainsString('window.CatarmanDom', $portalScript);
        self::assertStringContainsString('window.CatarmanFormatters', $billingFormatterScript);
        self::assertStringContainsString('window.CatarmanFormatters', $medicalFormatterScript);
        self::assertStringContainsString('window.CatarmanFormatters', $adoptionsFormatterScript);
        self::assertStringNotContainsString('const apiRequest =', $animalsScript);
        self::assertStringNotContainsString('const apiRequest =', $adoptionsScript);
        self::assertStringNotContainsString('const apiRequest =', $billingScript);
        self::assertStringNotContainsString('const apiRequest =', $medicalScript);
        self::assertStringNotContainsString('const apiRequest =', $usersScript);
        self::assertStringNotContainsString('const apiRequest =', $settingsScript);
        self::assertStringNotContainsString('const escapeHtml =', $animalsScript);
        self::assertStringNotContainsString('const escapeHtml =', $adoptionsScript);
        self::assertStringNotContainsString('const escapeHtml =', $billingScript);
        self::assertStringNotContainsString('const escapeHtml =', $medicalScript);
        self::assertStringNotContainsString('const escapeHtml =', $usersScript);
        self::assertStringNotContainsString('const escapeHtml =', $settingsScript);
    }

    public function testModuleUtilityWrappersDelegateToSharedBrowserHelpers(): void
    {
        $root = dirname(__DIR__, 2);
        $inventoryFormatters = (string) file_get_contents($root . '/public/assets/js/inventory/inventory-formatters.js');
        $kennelUtils = (string) file_get_contents($root . '/public/assets/js/kennels/kennel-utils.js');

        self::assertStringContainsString('window.CatarmanApi', $inventoryFormatters);
        self::assertStringContainsString('window.CatarmanDom', $inventoryFormatters);
        self::assertStringContainsString('window.CatarmanFormatters', $inventoryFormatters);
        self::assertStringContainsString('window.CatarmanApi', $kennelUtils);
        self::assertStringContainsString('window.CatarmanDom', $kennelUtils);
    }
}

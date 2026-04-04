<?php

declare(strict_types=1);

namespace Tests\Views;

require_once __DIR__ . '/ViewSmokeTestCase.php';

final class SearchViewTest extends ViewSmokeTestCase
{
    public function testSearchPageRendersTheCommandCenterMarkers(): void
    {
        $html = $this->renderApp('search.index', [
            'title' => 'Global Search',
            'searchQuery' => '',
            'searchFilters' => ['modules' => [], 'per_section' => 5],
            'availableSearchModules' => [
                ['key' => 'animals', 'label' => 'Animals'],
                ['key' => 'billing', 'label' => 'Billing'],
            ],
            'availableSearchSecondaryFilters' => [],
            'extraCss' => ['/assets/css/search.css'],
            'extraJs' => ['/assets/js/search.js'],
        ], '/search');

        self::assertStringContainsString('search-command-shell', $html);
        self::assertStringContainsString('search-filter-dock', $html);
        self::assertStringContainsString('search-results-ledger', $html);
    }

    public function testSearchPageRendersPresetShellMarkers(): void
    {
        $html = $this->renderApp('search.index', [
            'title' => 'Global Search',
            'searchQuery' => '',
            'searchFilters' => ['modules' => [], 'per_section' => 5],
            'availableSearchModules' => [
                ['key' => 'animals', 'label' => 'Animals'],
                ['key' => 'billing', 'label' => 'Billing'],
            ],
            'availableSearchSecondaryFilters' => [],
            'extraCss' => ['/assets/css/search.css'],
            'extraJs' => ['/assets/js/search.js'],
        ], '/search');

        self::assertStringContainsString('search-presets', $html);
        self::assertStringContainsString('data-search-preset-list', $html);
        self::assertStringContainsString('data-search-save-preset', $html);
    }

    public function testSearchScriptDeclaresPresetPersistence(): void
    {
        $script = (string) file_get_contents(dirname(__DIR__, 2) . '/public/assets/js/search.js');

        self::assertStringContainsString('catarman:search-presets', $script);
        self::assertStringContainsString('localStorage', $script);
        self::assertStringContainsString('renderPresets', $script);
    }
}

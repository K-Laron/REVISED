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
}

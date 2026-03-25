<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Middleware\CsrfMiddleware;
use App\Services\SearchService;

class SearchController
{
    private SearchService $search;

    public function __construct()
    {
        $this->search = new SearchService();
    }

    public function index(Request $request): Response
    {
        $query = trim((string) $request->query('q', ''));
        $authUser = $request->attribute('auth_user');
        $filters = [
            'modules' => $request->query('modules', []),
            'per_section' => (int) $request->query('per_section', 5),
            'date_from' => trim((string) $request->query('date_from', '')),
            'date_to' => trim((string) $request->query('date_to', '')),
            'animals_status' => trim((string) $request->query('animals_status', '')),
            'medical_type' => trim((string) $request->query('medical_type', '')),
            'adoption_status' => trim((string) $request->query('adoption_status', '')),
            'billing_status' => trim((string) $request->query('billing_status', '')),
            'inventory_status' => trim((string) $request->query('inventory_status', '')),
            'users_status' => trim((string) $request->query('users_status', '')),
            'status' => trim((string) $request->query('status', '')),
        ];

        return Response::html(View::render('search.index', [
            'title' => 'Global Search',
            'extraCss' => ['/assets/css/search.css'],
            'extraJs' => ['/assets/js/search.js'],
            'csrfToken' => CsrfMiddleware::token(),
            'user' => $authUser,
            'searchQuery' => $query,
            'searchFilters' => $filters,
            'availableSearchModules' => $this->search->availableModules(is_array($authUser) ? $authUser : []),
            'availableSearchSecondaryFilters' => $this->search->availableSecondaryFilters(is_array($authUser) ? $authUser : []),
        ], 'layouts.app'));
    }

    public function globalResults(Request $request): Response
    {
        $query = trim((string) $request->query('q', ''));
        $user = $request->attribute('auth_user');
        $filters = [
            'modules' => $request->query('modules', []),
            'per_section' => (int) $request->query('per_section', 5),
            'date_from' => trim((string) $request->query('date_from', '')),
            'date_to' => trim((string) $request->query('date_to', '')),
            'animals_status' => trim((string) $request->query('animals_status', '')),
            'medical_type' => trim((string) $request->query('medical_type', '')),
            'adoption_status' => trim((string) $request->query('adoption_status', '')),
            'billing_status' => trim((string) $request->query('billing_status', '')),
            'inventory_status' => trim((string) $request->query('inventory_status', '')),
            'users_status' => trim((string) $request->query('users_status', '')),
            'status' => trim((string) $request->query('status', '')),
        ];

        return Response::success(
            $this->search->search($query, is_array($user) ? $user : [], $filters),
            'Search results retrieved successfully.'
        );
    }
}

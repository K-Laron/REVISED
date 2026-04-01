<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Middleware\CsrfMiddleware;
use App\Services\DashboardService;

class DashboardController
{
    private DashboardService $dashboard;

    public function __construct()
    {
        $this->dashboard = new DashboardService();
    }

    public function index(Request $request): Response
    {
        return Response::html(View::render('dashboard.index', [
            'user' => $request->attribute('auth_user'),
            'csrfToken' => CsrfMiddleware::token(),
            'title' => 'Dashboard',
            'extraCss' => ['/assets/css/dashboard.css'],
            'extraJs' => ['/assets/vendor/chart.js/chart.umd.js', '/assets/js/dashboard.js'],
        ], 'layouts.app'));
    }

    public function stats(Request $request): Response
    {
        return Response::success($this->dashboard->stats(), 'Dashboard stats retrieved successfully.');
    }

    public function intakeChart(Request $request): Response
    {
        return Response::success($this->dashboard->intakeChart(), 'Dashboard intake chart retrieved successfully.');
    }

    public function adoptionChart(Request $request): Response
    {
        return Response::success($this->dashboard->adoptionChart(), 'Dashboard adoption chart retrieved successfully.');
    }

    public function occupancyChart(Request $request): Response
    {
        return Response::success($this->dashboard->occupancyChart(), 'Dashboard occupancy chart retrieved successfully.');
    }

    public function medicalChart(Request $request): Response
    {
        return Response::success($this->dashboard->medicalChart(), 'Dashboard medical chart retrieved successfully.');
    }

    public function recentActivity(Request $request): Response
    {
        return Response::success($this->dashboard->recentActivity(), 'Dashboard activity retrieved successfully.');
    }
}

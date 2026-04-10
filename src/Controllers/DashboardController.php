<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\Concerns\RendersViews;
use App\Core\Request;
use App\Core\Response;
use App\Middleware\CsrfMiddleware;
use App\Services\DashboardService;

class DashboardController
{
    use RendersViews;

    public function __construct(
        private readonly DashboardService $dashboard
    ) {
    }

    public function index(Request $request): Response
    {
        $user = $request->attribute('auth_user');
        $actionQueue = [];

        try {
            $actionQueue = $this->dashboard->actionQueue($user ?? []);
        } catch (\Throwable) {
            $actionQueue = [];
        }

        return $this->renderAppView('dashboard.index', [
            'user' => $user,
            'csrfToken' => CsrfMiddleware::token(),
            'title' => 'Dashboard',
            'extraCss' => ['/assets/css/dashboard.css'],
            'extraJs' => ['/assets/vendor/chart.js/chart.umd.js', '/assets/js/dashboard.js'],
            'actionQueue' => $actionQueue,
        ]);
    }

    public function stats(Request $request): Response
    {
        return Response::success($this->dashboard->stats(), 'Dashboard stats retrieved successfully.');
    }

    public function bootstrapData(Request $request): Response
    {
        return Response::success(
            $this->dashboard->bootstrap(),
            'Dashboard bootstrap retrieved successfully.'
        );
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

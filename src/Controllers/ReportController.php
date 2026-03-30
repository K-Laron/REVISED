<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\Concerns\InteractsWithApi;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Helpers\Validator;
use App\Middleware\CsrfMiddleware;
use App\Services\ExportService;
use App\Services\ReportService;
use App\Support\Pagination;
use RuntimeException;

class ReportController
{
    use InteractsWithApi;

    private ReportService $reports;
    private ExportService $exports;

    public function __construct()
    {
        $this->reports = new ReportService();
        $this->exports = new ExportService();
    }

    public function index(Request $request): Response
    {
        $authUser = $this->currentUser($request);
        $canViewAuditTrail = (($authUser['role_name'] ?? null) === 'super_admin');

        return Response::html(View::render('reports.index', [
            'title' => 'Reports & Analytics',
            'extraCss' => ['/assets/css/reports.css'],
            'extraJs' => ['/assets/js/reports.js'],
            'csrfToken' => CsrfMiddleware::token(),
            'templates' => $this->reports->templates((int) $authUser['id']),
            'canViewAuditTrail' => $canViewAuditTrail,
        ], 'layouts.app'));
    }

    public function viewer(Request $request): Response
    {
        return Response::html(View::render('reports.viewer', [
            'title' => 'Report Viewer',
            'extraCss' => ['/assets/css/reports.css'],
            'extraJs' => ['/assets/js/reports.js'],
        ], 'layouts.app'));
    }

    public function generate(Request $request): Response
    {
        $validator = (new Validator($request->query()))->rules([
            'report_type' => 'required|in:intake,medical,adoptions,billing,inventory,census',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'group_by' => 'nullable|in:day,week,month,quarter,year',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $report = $this->reports->generate((string) $request->query('report_type'), $request->query());

        return Response::success($report, 'Report generated successfully.');
    }

    public function exportCsv(Request $request): Response
    {
        try {
            $report = $this->reports->generate((string) $request->query('report_type'), $request->query());
            $relativePath = $this->exports->reportCsv($report);
        } catch (RuntimeException $exception) {
            return Response::error(409, 'REPORT_EXPORT_BLOCKED', $exception->getMessage());
        }

        $path = dirname(__DIR__, 2) . '/' . $relativePath;

        return $this->fileDownloadResponse($path, 'text/csv; charset=utf-8', basename($path));
    }

    public function exportPdf(Request $request): Response
    {
        try {
            $report = $this->reports->generate((string) $request->query('report_type'), $request->query());
            $relativePath = $this->exports->reportPdf($report);
        } catch (RuntimeException $exception) {
            return Response::error(409, 'REPORT_EXPORT_BLOCKED', $exception->getMessage());
        }

        $path = dirname(__DIR__, 2) . '/' . $relativePath;
        $disposition = strtolower((string) $request->query('disposition', 'attachment')) === 'inline'
            ? 'inline'
            : 'attachment';

        return $this->fileDownloadResponse($path, 'application/pdf', basename($path), $disposition);
    }

    public function listTemplates(Request $request): Response
    {
        $authUser = $this->currentUser($request);

        return Response::success($this->reports->templates((int) $authUser['id']), 'Report templates retrieved successfully.');
    }

    public function saveTemplate(Request $request): Response
    {
        $validator = (new Validator($request->body()))->rules([
            'name' => 'required|string|min:3|max:200',
            'report_type' => 'required|in:intake,medical,adoptions,billing,inventory,census',
            'configuration' => 'required|array',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $authUserId = $this->currentUserId($request);
        $template = $this->reports->saveTemplate(
            (string) $request->body('name'),
            (string) $request->body('report_type'),
            $request->body('configuration', []),
            $authUserId
        );

        return Response::success($template, 'Report template saved successfully.');
    }

    public function animalDossier(Request $request, string $animalId): Response
    {
        try {
            $dossier = $this->reports->animalDossier((int) $animalId);
            $relativePath = $this->exports->animalDossierPdf($dossier);
        } catch (RuntimeException $exception) {
            return Response::error(404, 'NOT_FOUND', $exception->getMessage());
        }

        $path = dirname(__DIR__, 2) . '/' . $relativePath;

        return $this->fileDownloadResponse($path, 'application/pdf', basename($path));
    }

    public function auditTrail(Request $request): Response
    {
        $page = Pagination::page($request->query('page'));
        $perPage = Pagination::perPage($request->query('per_page'), 20);
        $result = $this->reports->auditTrail($request->query(), $page, $perPage);

        return $this->paginatedSuccess($result, $page, $perPage, 'Audit trail retrieved successfully.');
    }
}

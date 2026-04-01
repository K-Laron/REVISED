<?php

declare(strict_types=1);

$router->get('/api/reports/generate', \App\Controllers\ReportController::class . '@generate', ['cors', 'auth', 'perm:reports.read']);
$router->get('/api/reports/export/csv', \App\Controllers\ReportController::class . '@exportCsv', ['cors', 'auth', 'perm:reports.export']);
$router->get('/api/reports/export/pdf', \App\Controllers\ReportController::class . '@exportPdf', ['cors', 'auth', 'perm:reports.export']);
$router->get('/api/reports/templates', \App\Controllers\ReportController::class . '@listTemplates', ['cors', 'auth', 'perm:reports.read']);
$router->post('/api/reports/templates', \App\Controllers\ReportController::class . '@saveTemplate', ['cors', 'csrf', 'auth', 'perm:reports.create']);
$router->get('/api/reports/animals/{animalId}/dossier', \App\Controllers\ReportController::class . '@animalDossier', ['cors', 'auth', 'perm:reports.export']);
$router->get('/api/reports/audit-trail', \App\Controllers\ReportController::class . '@auditTrail', ['cors', 'auth', 'role:super_admin']);

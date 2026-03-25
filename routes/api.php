<?php

declare(strict_types=1);

use App\Core\Response;
use App\Controllers\AnimalController;
use App\Controllers\AdoptionController;
use App\Controllers\AdopterPortalController;
use App\Controllers\AuthController;
use App\Controllers\BillingController;
use App\Controllers\BreedController;
use App\Controllers\DashboardController;
use App\Controllers\InventoryController;
use App\Controllers\KennelController;
use App\Controllers\MedicalController;
use App\Controllers\NotificationController;
use App\Controllers\QrCodeController;
use App\Controllers\ReportController;
use App\Controllers\RoleController;
use App\Controllers\SearchController;
use App\Controllers\SystemController;
use App\Controllers\UserController;
use App\Helpers\Validator;

$router->get('/api/ping', static function () {
    return Response::success([
        'timestamp' => date(DATE_ATOM),
        'status' => 'ok',
    ], 'API is reachable.');
});

$router->post('/api/validate-test', static function ($request) {
    $validator = new Validator($request->body());
    $validator->rules([
        'email' => 'required|email|max:255',
        'name' => 'required|string|min:2|max:100',
    ]);

    if ($validator->fails()) {
        return Response::error(422, 'VALIDATION_ERROR', 'The given data was invalid.', $validator->errors());
    }

    return Response::success($request->body(), 'Validation passed.');
});

$router->get('/api/system/health', SystemController::class . '@health', ['cors']);
$router->get('/api/system/settings', SystemController::class . '@settings', ['cors', 'auth']);
$router->put('/api/system/settings', SystemController::class . '@updateSettings', ['cors', 'csrf', 'auth', 'role:super_admin']);
$router->put('/api/system/maintenance', SystemController::class . '@updateMaintenance', ['cors', 'csrf', 'auth', 'role:super_admin']);
$router->get('/api/system/readiness', SystemController::class . '@readiness', ['cors', 'auth']);
$router->post('/api/system/backup', SystemController::class . '@createBackup', ['cors', 'csrf', 'auth', 'role:super_admin']);
$router->get('/api/system/backups', SystemController::class . '@listBackups', ['cors', 'auth', 'role:super_admin']);
$router->post('/api/system/backups/{id}/restore', SystemController::class . '@restoreBackup', ['cors', 'csrf', 'auth', 'role:super_admin']);

$router->post('/api/auth/login', AuthController::class . '@login', ['throttle:5', 'cors', 'csrf', 'guest']);
$router->post('/api/auth/logout', AuthController::class . '@logout', ['cors', 'csrf', 'auth']);
$router->post('/api/auth/forgot-password', AuthController::class . '@forgotPassword', ['throttle:3', 'cors', 'csrf', 'guest']);
$router->post('/api/auth/reset-password', AuthController::class . '@resetPassword', ['throttle:3', 'cors', 'csrf', 'guest']);
$router->get('/api/auth/me', AuthController::class . '@me', ['cors', 'auth']);
$router->put('/api/auth/profile', AuthController::class . '@updateProfile', ['cors', 'csrf', 'auth']);
$router->put('/api/auth/change-password', AuthController::class . '@changePassword', ['cors', 'csrf', 'auth']);

$router->get('/api/dashboard/stats', DashboardController::class . '@stats', ['cors', 'auth']);
$router->get('/api/dashboard/charts/intake', DashboardController::class . '@intakeChart', ['cors', 'auth']);
$router->get('/api/dashboard/charts/adoptions', DashboardController::class . '@adoptionChart', ['cors', 'auth']);
$router->get('/api/dashboard/charts/occupancy', DashboardController::class . '@occupancyChart', ['cors', 'auth']);
$router->get('/api/dashboard/charts/medical', DashboardController::class . '@medicalChart', ['cors', 'auth']);
$router->get('/api/dashboard/activity', DashboardController::class . '@recentActivity', ['cors', 'auth']);

$router->get('/api/breeds', BreedController::class . '@list', ['cors', 'auth']);
$router->get('/api/animals', AnimalController::class . '@list', ['cors', 'auth', 'perm:animals.read']);
$router->post('/api/animals', AnimalController::class . '@store', ['cors', 'csrf', 'auth', 'perm:animals.create']);
$router->get('/api/animals/{id}/timeline', AnimalController::class . '@timeline', ['cors', 'auth', 'perm:animals.read']);
$router->put('/api/animals/{id}/status', AnimalController::class . '@updateStatus', ['cors', 'csrf', 'auth', 'perm:animals.update']);
$router->post('/api/animals/{id}/photos', AnimalController::class . '@uploadPhoto', ['cors', 'csrf', 'auth', 'perm:animals.update']);
$router->delete('/api/animals/{id}/photos/{photoId}', AnimalController::class . '@deletePhoto', ['cors', 'csrf', 'auth', 'perm:animals.update']);
$router->get('/api/animals/{id}/qr', QrCodeController::class . '@generate', ['cors', 'auth', 'perm:animals.read']);
$router->get('/api/animals/{id}/qr/download', QrCodeController::class . '@download', ['cors', 'auth', 'perm:animals.read']);
$router->get('/api/animals/scan/{qrData}', QrCodeController::class . '@scan', ['cors', 'auth']);
$router->get('/api/animals/{id}', AnimalController::class . '@get', ['cors', 'auth', 'perm:animals.read']);
$router->put('/api/animals/{id}', AnimalController::class . '@update', ['cors', 'csrf', 'auth', 'perm:animals.update']);
$router->delete('/api/animals/{id}', AnimalController::class . '@destroy', ['cors', 'csrf', 'auth', 'perm:animals.delete']);
$router->post('/api/animals/{id}/restore', AnimalController::class . '@restore', ['cors', 'csrf', 'auth', 'perm:animals.delete']);

$router->get('/api/billing/invoices', BillingController::class . '@listInvoices', ['cors', 'auth', 'perm:billing.read']);
$router->post('/api/billing/invoices', BillingController::class . '@storeInvoice', ['cors', 'csrf', 'auth', 'perm:billing.create']);
$router->put('/api/billing/invoices/{id}', BillingController::class . '@updateInvoice', ['cors', 'csrf', 'auth', 'perm:billing.update']);
$router->post('/api/billing/invoices/{id}/void', BillingController::class . '@voidInvoice', ['cors', 'csrf', 'auth', 'perm:billing.delete']);
$router->get('/api/billing/invoices/{id}/pdf', BillingController::class . '@invoicePdf', ['cors', 'auth', 'perm:billing.read']);
$router->post('/api/billing/invoices/{id}/payments', BillingController::class . '@recordPayment', ['cors', 'csrf', 'auth', 'perm:billing.create']);
$router->get('/api/billing/payments', BillingController::class . '@listPayments', ['cors', 'auth', 'perm:billing.read']);
$router->get('/api/billing/payments/{id}/receipt', BillingController::class . '@receiptPdf', ['cors', 'auth', 'perm:billing.read']);
$router->get('/api/billing/fee-schedule', BillingController::class . '@feeSchedule', ['cors', 'auth', 'perm:billing.read']);
$router->post('/api/billing/fee-schedule', BillingController::class . '@storeFee', ['cors', 'csrf', 'auth', 'perm:billing.create']);
$router->put('/api/billing/fee-schedule/{id}', BillingController::class . '@updateFee', ['cors', 'csrf', 'auth', 'perm:billing.update']);
$router->get('/api/billing/stats', BillingController::class . '@stats', ['cors', 'auth', 'perm:billing.read']);

$router->get('/api/inventory', InventoryController::class . '@list', ['cors', 'auth', 'perm:inventory.read']);
$router->post('/api/inventory', InventoryController::class . '@store', ['cors', 'csrf', 'auth', 'perm:inventory.create']);
$router->get('/api/inventory/categories', InventoryController::class . '@categories', ['cors', 'auth', 'perm:inventory.read']);
$router->get('/api/inventory/alerts', InventoryController::class . '@alerts', ['cors', 'auth', 'perm:inventory.read']);
$router->get('/api/inventory/stats', InventoryController::class . '@stats', ['cors', 'auth', 'perm:inventory.read']);
$router->get('/api/inventory/{id}', InventoryController::class . '@get', ['cors', 'auth', 'perm:inventory.read']);
$router->put('/api/inventory/{id}', InventoryController::class . '@update', ['cors', 'csrf', 'auth', 'perm:inventory.update']);
$router->delete('/api/inventory/{id}', InventoryController::class . '@destroy', ['cors', 'csrf', 'auth', 'perm:inventory.delete']);
$router->post('/api/inventory/{id}/stock-in', InventoryController::class . '@stockIn', ['cors', 'csrf', 'auth', 'perm:inventory.update']);
$router->post('/api/inventory/{id}/stock-out', InventoryController::class . '@stockOut', ['cors', 'csrf', 'auth', 'perm:inventory.update']);
$router->post('/api/inventory/{id}/adjust', InventoryController::class . '@adjust', ['cors', 'csrf', 'auth', 'perm:inventory.update']);
$router->get('/api/inventory/{id}/transactions', InventoryController::class . '@transactions', ['cors', 'auth', 'perm:inventory.read']);
$router->post('/api/inventory/categories', InventoryController::class . '@storeCategory', ['cors', 'csrf', 'auth', 'perm:inventory.create']);

$router->get('/api/kennels', KennelController::class . '@list', ['cors', 'auth', 'perm:kennels.read']);
$router->post('/api/kennels', KennelController::class . '@store', ['cors', 'csrf', 'auth', 'perm:kennels.create']);
$router->put('/api/kennels/{id}', KennelController::class . '@update', ['cors', 'csrf', 'auth', 'perm:kennels.update']);
$router->delete('/api/kennels/{id}', KennelController::class . '@destroy', ['cors', 'csrf', 'auth', 'perm:kennels.delete']);
$router->post('/api/kennels/{id}/assign', KennelController::class . '@assignAnimal', ['cors', 'csrf', 'auth', 'perm:kennels.update']);
$router->post('/api/kennels/{id}/release', KennelController::class . '@releaseAnimal', ['cors', 'csrf', 'auth', 'perm:kennels.update']);
$router->get('/api/kennels/{id}/history', KennelController::class . '@history', ['cors', 'auth', 'perm:kennels.read']);
$router->get('/api/kennels/stats', KennelController::class . '@stats', ['cors', 'auth', 'perm:kennels.read']);
$router->post('/api/kennels/{id}/maintenance', KennelController::class . '@logMaintenance', ['cors', 'csrf', 'auth', 'perm:kennels.update']);
$router->get('/api/kennels/{id}/maintenance', KennelController::class . '@maintenanceHistory', ['cors', 'auth', 'perm:kennels.read']);

$router->get('/api/medical', MedicalController::class . '@list', ['cors', 'auth', 'perm:medical.read']);
$router->get('/api/medical/animal/{animalId}', MedicalController::class . '@byAnimal', ['cors', 'auth', 'perm:medical.read']);
$router->post('/api/medical/vaccination', MedicalController::class . '@storeVaccination', ['cors', 'csrf', 'auth', 'perm:medical.create']);
$router->post('/api/medical/surgery', MedicalController::class . '@storeSurgery', ['cors', 'csrf', 'auth', 'perm:medical.create']);
$router->post('/api/medical/examination', MedicalController::class . '@storeExamination', ['cors', 'csrf', 'auth', 'perm:medical.create']);
$router->post('/api/medical/treatment', MedicalController::class . '@storeTreatment', ['cors', 'csrf', 'auth', 'perm:medical.create']);
$router->post('/api/medical/deworming', MedicalController::class . '@storeDeworming', ['cors', 'csrf', 'auth', 'perm:medical.create']);
$router->post('/api/medical/euthanasia', MedicalController::class . '@storeEuthanasia', ['cors', 'csrf', 'auth', 'perm:medical.create']);
$router->get('/api/medical/due-vaccinations', MedicalController::class . '@dueVaccinations', ['cors', 'auth', 'perm:medical.read']);
$router->get('/api/medical/due-dewormings', MedicalController::class . '@dueDewormings', ['cors', 'auth', 'perm:medical.read']);
$router->get('/api/medical/form-config/{type}', MedicalController::class . '@formConfig', ['cors', 'auth', 'perm:medical.read']);
$router->put('/api/medical/{id}', MedicalController::class . '@update', ['cors', 'csrf', 'auth', 'perm:medical.update']);
$router->delete('/api/medical/{id}', MedicalController::class . '@destroy', ['cors', 'csrf', 'auth', 'perm:medical.delete']);

$router->get('/api/adoptions', AdoptionController::class . '@list', ['cors', 'auth', 'perm:adoptions.read']);
$router->get('/api/adoptions/pipeline-stats', AdoptionController::class . '@pipelineStats', ['cors', 'auth', 'perm:adoptions.read']);
$router->get('/api/adoptions/seminars', AdoptionController::class . '@listSeminars', ['cors', 'auth', 'perm:adoptions.read']);
$router->post('/api/adoptions/seminars', AdoptionController::class . '@createSeminar', ['cors', 'csrf', 'auth', 'perm:adoptions.create']);
$router->post('/api/adoptions/seminars/{id}/attendees', AdoptionController::class . '@addAttendee', ['cors', 'csrf', 'auth', 'perm:adoptions.update']);
$router->put('/api/adoptions/seminars/{id}/attendance', AdoptionController::class . '@updateAttendance', ['cors', 'csrf', 'auth', 'perm:adoptions.update']);
$router->put('/api/adoptions/interviews/{id}', AdoptionController::class . '@updateInterview', ['cors', 'csrf', 'auth', 'perm:adoptions.update']);
$router->get('/api/adoptions/{id}/certificate', AdoptionController::class . '@certificate', ['cors', 'auth', 'perm:adoptions.read']);
$router->get('/api/adoptions/{id}', AdoptionController::class . '@get', ['cors', 'auth', 'perm:adoptions.read']);
$router->put('/api/adoptions/{id}/status', AdoptionController::class . '@updateStatus', ['cors', 'csrf', 'auth', 'perm:adoptions.update']);
$router->put('/api/adoptions/{id}/reject', AdoptionController::class . '@reject', ['cors', 'csrf', 'auth', 'perm:adoptions.update']);
$router->post('/api/adoptions/{id}/interview', AdoptionController::class . '@scheduleInterview', ['cors', 'csrf', 'auth', 'perm:adoptions.update']);
$router->post('/api/adoptions/{id}/complete', AdoptionController::class . '@complete', ['cors', 'csrf', 'auth', 'perm:adoptions.update']);
$router->post('/api/adopt/register', AdopterPortalController::class . '@register', ['throttle:5', 'cors', 'csrf', 'guest']);
$router->post('/api/adopt/apply', AdopterPortalController::class . '@apply', ['throttle:3', 'cors', 'csrf', 'auth', 'role:adopter']);
$router->get('/api/adopt/my-applications', AdopterPortalController::class . '@myApplications', ['cors', 'auth', 'role:adopter']);

$router->get('/api/users', UserController::class . '@list', ['cors', 'auth', 'perm:users.read']);
$router->post('/api/users', UserController::class . '@store', ['cors', 'csrf', 'auth', 'perm:users.create']);
$router->get('/api/users/{id}', UserController::class . '@get', ['cors', 'auth', 'perm:users.read']);
$router->put('/api/users/{id}', UserController::class . '@update', ['cors', 'csrf', 'auth', 'perm:users.update']);
$router->delete('/api/users/{id}', UserController::class . '@destroy', ['cors', 'csrf', 'auth', 'perm:users.delete']);
$router->post('/api/users/{id}/restore', UserController::class . '@restore', ['cors', 'csrf', 'auth', 'perm:users.delete']);
$router->put('/api/users/{id}/role', UserController::class . '@changeRole', ['cors', 'csrf', 'auth', 'perm:users.update']);
$router->post('/api/users/{id}/reset-password', UserController::class . '@resetPassword', ['cors', 'csrf', 'auth', 'perm:users.update']);
$router->get('/api/users/{id}/sessions', UserController::class . '@sessions', ['cors', 'auth', 'perm:users.read']);
$router->delete('/api/users/{id}/sessions/{sessionId}', UserController::class . '@destroySession', ['cors', 'csrf', 'auth', 'perm:users.update']);

$router->get('/api/roles', RoleController::class . '@list', ['cors', 'auth', 'perm:users.read']);
$router->get('/api/roles/{id}/permissions', RoleController::class . '@permissions', ['cors', 'auth', 'role:super_admin']);
$router->put('/api/roles/{id}/permissions', RoleController::class . '@updatePermissions', ['cors', 'csrf', 'auth', 'role:super_admin']);

$router->get('/api/reports/generate', ReportController::class . '@generate', ['cors', 'auth', 'perm:reports.read']);
$router->get('/api/reports/export/csv', ReportController::class . '@exportCsv', ['cors', 'auth', 'perm:reports.export']);
$router->get('/api/reports/export/pdf', ReportController::class . '@exportPdf', ['cors', 'auth', 'perm:reports.export']);
$router->get('/api/reports/templates', ReportController::class . '@listTemplates', ['cors', 'auth', 'perm:reports.read']);
$router->post('/api/reports/templates', ReportController::class . '@saveTemplate', ['cors', 'csrf', 'auth', 'perm:reports.create']);
$router->get('/api/reports/animals/{animalId}/dossier', ReportController::class . '@animalDossier', ['cors', 'auth', 'perm:reports.export']);
$router->get('/api/reports/audit-trail', ReportController::class . '@auditTrail', ['cors', 'auth', 'role:super_admin']);

$router->get('/api/notifications', NotificationController::class . '@list', ['cors', 'auth']);
$router->get('/api/notifications/unread-count', NotificationController::class . '@unreadCount', ['cors', 'auth']);
$router->put('/api/notifications/{id}/read', NotificationController::class . '@markRead', ['cors', 'csrf', 'auth']);
$router->put('/api/notifications/read-all', NotificationController::class . '@markAllRead', ['cors', 'csrf', 'auth']);
$router->get('/api/search/global', SearchController::class . '@globalResults', ['cors', 'auth']);

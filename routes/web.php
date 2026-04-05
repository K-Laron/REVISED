<?php

declare(strict_types=1);

use App\Core\Response;
use App\Core\View;
use App\Controllers\AnimalController;
use App\Controllers\AdoptionController;
use App\Controllers\AdopterPortalController;
use App\Controllers\AuthController;
use App\Controllers\BillingController;
use App\Controllers\DashboardController;
use App\Controllers\InventoryController;
use App\Controllers\KennelController;
use App\Controllers\MedicalController;
use App\Controllers\ReportController;
use App\Controllers\SearchController;
use App\Controllers\SettingsController;
use App\Controllers\UserController;
use App\Controllers\WelcomeController;

$router->get('/', WelcomeController::class . '@index');

$router->get('/login', AuthController::class . '@showLogin', ['guest']);
$router->get('/forgot-password', AuthController::class . '@showForgotPassword', ['guest']);
$router->get('/reset-password/{token}', AuthController::class . '@showResetPassword', ['guest']);
$router->get('/force-password-change', AuthController::class . '@showForcePasswordChange', ['auth']);
$router->get('/adopt', AdopterPortalController::class . '@landing');
$router->get('/adopt/animals', AdopterPortalController::class . '@animals');
$router->get('/adopt/animals/{id}', AdopterPortalController::class . '@animalDetail');
$router->get('/adopt/register', AdopterPortalController::class . '@showRegister', ['guest']);
$router->post('/adopt/register', AdopterPortalController::class . '@register', ['guest', 'csrf']);
$router->get('/adopt/apply', AdopterPortalController::class . '@showApply', ['auth', 'role:adopter']);
$router->get('/dashboard', DashboardController::class . '@index', ['auth', 'role:super_admin,shelter_head']);
$router->get('/animals', AnimalController::class . '@index', ['auth', 'perm:animals.read']);
$router->get('/animals/create', AnimalController::class . '@create', ['auth', 'perm:animals.create']);
$router->get('/animals/{id}/edit', AnimalController::class . '@edit', ['auth', 'perm:animals.update']);
$router->get('/animals/{id}', AnimalController::class . '@show', ['auth', 'perm:animals.read']);
$router->get('/billing', BillingController::class . '@index', ['auth', 'perm:billing.read']);
$router->get('/billing/invoices/create', BillingController::class . '@createInvoice', ['auth', 'perm:billing.create']);
$router->get('/billing/invoices/{id}', BillingController::class . '@showInvoice', ['auth', 'perm:billing.read']);
$router->get('/inventory', InventoryController::class . '@index', ['auth', 'perm:inventory.read']);
$router->get('/inventory/{id}', InventoryController::class . '@show', ['auth', 'perm:inventory.read']);
$router->get('/kennels', KennelController::class . '@index', ['auth', 'perm:kennels.read']);
$router->get('/medical', MedicalController::class . '@index', ['auth', 'perm:medical.read']);
$router->get('/medical/create/{animalId}', MedicalController::class . '@create', ['auth', 'perm:medical.create']);
$router->get('/medical/{id}', MedicalController::class . '@show', ['auth', 'perm:medical.read']);
$router->get('/adoptions', AdoptionController::class . '@index', ['auth', 'perm:adoptions.read']);
$router->get('/adoptions/{id}', AdoptionController::class . '@show', ['auth', 'perm:adoptions.read']);
$router->get('/users', UserController::class . '@index', ['auth', 'perm:users.read']);
$router->get('/users/create', UserController::class . '@create', ['auth', 'perm:users.create']);
$router->get('/users/{id}', UserController::class . '@show', ['auth', 'perm:users.read']);
$router->get('/reports', ReportController::class . '@index', ['auth', 'perm:reports.read']);
$router->get('/reports/viewer', ReportController::class . '@viewer', ['auth', 'perm:reports.read']);
$router->get('/search', SearchController::class . '@index', ['auth']);
$router->get('/settings', SettingsController::class . '@index', ['auth']);

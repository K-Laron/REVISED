<?php

declare(strict_types=1);

$router->get('/api/billing/invoices', \App\Controllers\BillingController::class . '@listInvoices', ['cors', 'auth', 'perm:billing.read']);
$router->post('/api/billing/invoices', \App\Controllers\BillingController::class . '@storeInvoice', ['cors', 'csrf', 'auth', 'perm:billing.create']);
$router->put('/api/billing/invoices/{id}', \App\Controllers\BillingController::class . '@updateInvoice', ['cors', 'csrf', 'auth', 'perm:billing.update']);
$router->post('/api/billing/invoices/{id}/void', \App\Controllers\BillingController::class . '@voidInvoice', ['cors', 'csrf', 'auth', 'perm:billing.delete']);
$router->get('/api/billing/invoices/{id}/pdf', \App\Controllers\BillingController::class . '@invoicePdf', ['cors', 'auth', 'perm:billing.read']);
$router->post('/api/billing/invoices/{id}/payments', \App\Controllers\BillingController::class . '@recordPayment', ['cors', 'csrf', 'auth', 'perm:billing.create']);
$router->get('/api/billing/payments', \App\Controllers\BillingController::class . '@listPayments', ['cors', 'auth', 'perm:billing.read']);
$router->get('/api/billing/payments/{id}/receipt', \App\Controllers\BillingController::class . '@receiptPdf', ['cors', 'auth', 'perm:billing.read']);
$router->get('/api/billing/fee-schedule', \App\Controllers\BillingController::class . '@feeSchedule', ['cors', 'auth', 'perm:billing.read']);
$router->post('/api/billing/fee-schedule', \App\Controllers\BillingController::class . '@storeFee', ['cors', 'csrf', 'auth', 'perm:billing.create']);
$router->put('/api/billing/fee-schedule/{id}', \App\Controllers\BillingController::class . '@updateFee', ['cors', 'csrf', 'auth', 'perm:billing.update']);
$router->get('/api/billing/stats', \App\Controllers\BillingController::class . '@stats', ['cors', 'auth', 'perm:billing.read']);

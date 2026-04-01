<?php

declare(strict_types=1);

$router->get('/api/medical', \App\Controllers\MedicalController::class . '@list', ['cors', 'auth', 'perm:medical.read']);
$router->get('/api/medical/animal/{animalId}', \App\Controllers\MedicalController::class . '@byAnimal', ['cors', 'auth', 'perm:medical.read']);
$router->post('/api/medical/vaccination', \App\Controllers\MedicalController::class . '@storeVaccination', ['cors', 'csrf', 'auth', 'perm:medical.create']);
$router->post('/api/medical/surgery', \App\Controllers\MedicalController::class . '@storeSurgery', ['cors', 'csrf', 'auth', 'perm:medical.create']);
$router->post('/api/medical/examination', \App\Controllers\MedicalController::class . '@storeExamination', ['cors', 'csrf', 'auth', 'perm:medical.create']);
$router->post('/api/medical/treatment', \App\Controllers\MedicalController::class . '@storeTreatment', ['cors', 'csrf', 'auth', 'perm:medical.create']);
$router->post('/api/medical/deworming', \App\Controllers\MedicalController::class . '@storeDeworming', ['cors', 'csrf', 'auth', 'perm:medical.create']);
$router->post('/api/medical/euthanasia', \App\Controllers\MedicalController::class . '@storeEuthanasia', ['cors', 'csrf', 'auth', 'perm:medical.create']);
$router->get('/api/medical/due-vaccinations', \App\Controllers\MedicalController::class . '@dueVaccinations', ['cors', 'auth', 'perm:medical.read']);
$router->get('/api/medical/due-dewormings', \App\Controllers\MedicalController::class . '@dueDewormings', ['cors', 'auth', 'perm:medical.read']);
$router->get('/api/medical/form-config/{type}', \App\Controllers\MedicalController::class . '@formConfig', ['cors', 'auth', 'perm:medical.read']);
$router->put('/api/medical/{id}', \App\Controllers\MedicalController::class . '@update', ['cors', 'csrf', 'auth', 'perm:medical.update']);
$router->delete('/api/medical/{id}', \App\Controllers\MedicalController::class . '@destroy', ['cors', 'csrf', 'auth', 'perm:medical.delete']);

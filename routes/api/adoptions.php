<?php

declare(strict_types=1);

$router->get('/api/adoptions', \App\Controllers\AdoptionController::class . '@list', ['cors', 'auth', 'perm:adoptions.read']);
$router->get('/api/adoptions/pipeline-stats', \App\Controllers\AdoptionController::class . '@pipelineStats', ['cors', 'auth', 'perm:adoptions.read']);
$router->get('/api/adoptions/seminars', \App\Controllers\AdoptionController::class . '@listSeminars', ['cors', 'auth', 'perm:adoptions.read']);
$router->post('/api/adoptions/seminars', \App\Controllers\AdoptionController::class . '@createSeminar', ['cors', 'csrf', 'auth', 'perm:adoptions.create']);
$router->post('/api/adoptions/seminars/{id}/attendees', \App\Controllers\AdoptionController::class . '@addAttendee', ['cors', 'csrf', 'auth', 'perm:adoptions.update']);
$router->put('/api/adoptions/seminars/{id}/attendance', \App\Controllers\AdoptionController::class . '@updateAttendance', ['cors', 'csrf', 'auth', 'perm:adoptions.update']);
$router->put('/api/adoptions/interviews/{id}', \App\Controllers\AdoptionController::class . '@updateInterview', ['cors', 'csrf', 'auth', 'perm:adoptions.update']);
$router->get('/api/adoptions/{id}/certificate', \App\Controllers\AdoptionController::class . '@certificate', ['cors', 'auth', 'perm:adoptions.read']);
$router->get('/api/adoptions/{id}', \App\Controllers\AdoptionController::class . '@get', ['cors', 'auth', 'perm:adoptions.read']);
$router->put('/api/adoptions/{id}/status', \App\Controllers\AdoptionController::class . '@updateStatus', ['cors', 'csrf', 'auth', 'perm:adoptions.update']);
$router->put('/api/adoptions/{id}/reject', \App\Controllers\AdoptionController::class . '@reject', ['cors', 'csrf', 'auth', 'perm:adoptions.update']);
$router->post('/api/adoptions/{id}/interview', \App\Controllers\AdoptionController::class . '@scheduleInterview', ['cors', 'csrf', 'auth', 'perm:adoptions.update']);
$router->post('/api/adoptions/{id}/complete', \App\Controllers\AdoptionController::class . '@complete', ['cors', 'csrf', 'auth', 'perm:adoptions.update']);
$router->post('/api/adopt/register', \App\Controllers\AdopterPortalController::class . '@register', ['throttle:5', 'cors', 'csrf', 'guest']);
$router->post('/api/adopt/apply', \App\Controllers\AdopterPortalController::class . '@apply', ['throttle:3', 'cors', 'csrf', 'auth', 'role:adopter']);
$router->get('/api/adopt/my-applications', \App\Controllers\AdopterPortalController::class . '@myApplications', ['cors', 'auth', 'role:adopter']);

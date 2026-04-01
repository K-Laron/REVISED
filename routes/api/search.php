<?php

declare(strict_types=1);

$router->get('/api/search/global', \App\Controllers\SearchController::class . '@globalResults', ['cors', 'auth']);

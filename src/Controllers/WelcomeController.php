<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;

class WelcomeController
{
    public function index(Request $request): Response
    {
        return Response::html(View::render('welcome', [
            'appName' => $GLOBALS['app']['name'] ?? ($_ENV['APP_NAME'] ?? 'Catarman Animal Shelter'),
        ]));
    }
}

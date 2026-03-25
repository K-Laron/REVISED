<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\AnimalService;

class BreedController
{
    public function list(Request $request): Response
    {
        $service = new AnimalService();

        return Response::success(
            $service->breeds((string) $request->query('species', '')),
            'Breeds retrieved successfully.'
        );
    }
}

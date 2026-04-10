<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\AnimalService;

class BreedController
{
    public function __construct(
        private readonly AnimalService $animals
    ) {
    }

    public function list(Request $request): Response
    {
        return Response::success(
            $this->animals->breeds((string) $request->query('species', '')),
            'Breeds retrieved successfully.'
        );
    }
}

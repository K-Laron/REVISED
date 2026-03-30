<?php

declare(strict_types=1);

namespace App\Controllers\Concerns;

use App\Core\Response;
use App\Core\View;

trait RendersViews
{
    protected function renderAppView(string $view, array $data = [], int $status = 200): Response
    {
        return Response::html(View::render($view, $data, 'layouts.app'), $status);
    }

    protected function renderPublicView(string $view, array $data = [], int $status = 200): Response
    {
        return Response::html(View::render($view, $data, 'layouts.public'), $status);
    }
}

<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use Closure;

class CorsMiddleware
{
    public function handle(Request $request, Closure $next, ?string $parameter = null): mixed
    {
        $config = require dirname(__DIR__, 2) . '/config/cors.php';
        $origin = (string) $request->header('Origin', '');
        $allowedOrigins = $config['allowed_origins'] ?? [];

        if ($origin !== '' && in_array($origin, $allowedOrigins, true)) {
            header('Access-Control-Allow-Origin: ' . $origin);
        } elseif ($origin === '') {
            header('Access-Control-Allow-Origin: ' . ($allowedOrigins[0] ?? '*'));
        }

        header('Vary: Origin');
        header('Access-Control-Allow-Methods: ' . implode(', ', $config['allowed_methods'] ?? []));
        header('Access-Control-Allow-Headers: ' . implode(', ', $config['allowed_headers'] ?? []));

        if (($config['supports_credentials'] ?? false) === true) {
            header('Access-Control-Allow-Credentials: true');
        }

        if ($request->method() === 'OPTIONS') {
            return Response::html('', 204);
        }

        return $next($request);
    }
}

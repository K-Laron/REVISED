<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\ExceptionHandler;
use App\Core\Request;
use Closure;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, ?string $parameter = null): mixed
    {
        $user = $request->attribute('auth_user');
        $permissions = $user['permissions'] ?? [];

        if ($user === null || !in_array((string) $parameter, $permissions, true)) {
            return ExceptionHandler::forbiddenResponse($request, 'You do not have permission to perform this action.');
        }

        return $next($request);
    }
}

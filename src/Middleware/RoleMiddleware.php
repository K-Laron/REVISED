<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\ExceptionHandler;
use App\Core\Request;
use Closure;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ?string $parameter = null): mixed
    {
        $user = $request->attribute('auth_user');
        $roles = array_filter(array_map('trim', explode(',', (string) $parameter)));

        if ($user === null || $roles === []) {
            return ExceptionHandler::forbiddenResponse($request, 'You do not have access to this resource.');
        }

        if (!in_array((string) ($user['role_name'] ?? ''), $roles, true)) {
            return ExceptionHandler::forbiddenResponse($request, 'You do not have access to this resource.');
        }

        return $next($request);
    }
}

<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;
use Closure;

class AuthMiddleware
{
    public function handle(Request $request, Closure $next, ?string $parameter = null): mixed
    {
        $authService = new AuthService();
        $user = $authService->userFromRequest($request);

        if ($user === null) {
            if ($request->expectsJson()) {
                return Response::error(401, 'UNAUTHORIZED', 'Authentication is required.');
            }

            return Response::redirect('/login');
        }

        $request->setAttribute('auth_user', $user);

        if (
            (int) ($user['force_password_change'] ?? 0) === 1
            && !in_array($request->path(), [
                '/force-password-change',
                '/api/auth/change-password',
                '/api/auth/logout',
                '/api/auth/me',
            ], true)
        ) {
            if ($request->expectsJson()) {
                return Response::error(403, 'FORCE_PASSWORD_CHANGE_REQUIRED', 'You must update your password before continuing.', [
                    'redirect' => '/force-password-change',
                ]);
            }

            return Response::redirect('/force-password-change');
        }

        return $next($request);
    }
}

<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;
use App\Support\LandingPage;
use Closure;

class GuestMiddleware
{
    public function handle(Request $request, Closure $next, ?string $parameter = null): mixed
    {
        $authService = new AuthService();
        $user = $authService->userFromRequest($request);

        if ($user !== null) {
            if ($request->expectsJson()) {
                return Response::error(403, 'FORBIDDEN', 'You are already authenticated.');
            }

            return Response::redirect($this->redirectPathForUser($user));
        }

        return $next($request);
    }

    private function redirectPathForUser(array $user): string
    {
        return LandingPage::forUser($user);
    }
}

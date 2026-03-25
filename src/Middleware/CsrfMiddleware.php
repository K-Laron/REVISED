<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use Closure;

class CsrfMiddleware
{
    public function handle(Request $request, Closure $next, ?string $parameter = null): mixed
    {
        self::token();

        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $next($request);
        }

        $token = (string) ($request->body('_token') ?? $request->header('X-CSRF-TOKEN', ''));
        if (!hash_equals((string) Session::get('_csrf_token', ''), $token)) {
            return Response::error(419, 'CSRF_TOKEN_MISMATCH', 'The submitted form is no longer valid.');
        }

        return $next($request);
    }

    public static function token(): string
    {
        $token = (string) Session::get('_csrf_token', '');

        if ($token === '') {
            return self::rotateToken();
        }

        return $token;
    }

    public static function rotateToken(): string
    {
        $token = bin2hex(random_bytes(32));
        Session::put('_csrf_token', $token);

        return $token;
    }
}

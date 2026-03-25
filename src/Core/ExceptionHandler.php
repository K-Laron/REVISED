<?php

declare(strict_types=1);

namespace App\Core;

use App\Support\SystemSettings;
use App\Support\LandingPage;
use ErrorException;
use Throwable;

class ExceptionHandler
{
    public static function register(array $appConfig): void
    {
        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }

            throw new ErrorException($message, 0, $severity, $file, $line);
        });

        set_exception_handler(static function (Throwable $exception) use ($appConfig): void {
            Logger::error($exception->getMessage(), [
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ]);

            $response = self::exceptionResponse($exception, (bool) ($appConfig['debug'] ?? false));
            $response->send();
        });
    }

    public static function bootTimestamp(): int
    {
        $directory = dirname(__DIR__, 2) . '/storage/runtime';
        $path = $directory . '/app_booted_at.txt';

        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        if (!is_file($path)) {
            file_put_contents($path, (string) time());
        }

        $value = (int) trim((string) file_get_contents($path));

        return $value > 0 ? $value : time();
    }

    public static function inMaintenanceMode(): bool
    {
        return (bool) SystemSettings::get('maintenance_mode_enabled', false)
            || is_file(dirname(__DIR__, 2) . '/storage/maintenance.flag');
    }

    public static function maintenanceResponse(Request $request): Response
    {
        return self::httpErrorResponse(
            $request,
            503,
            'SERVICE_UNAVAILABLE',
            (string) SystemSettings::get('maintenance_message', 'The system is currently under maintenance.')
        );
    }

    public static function forbiddenResponse(Request $request, string $message = 'You do not have access to this resource.'): Response
    {
        return self::httpErrorResponse($request, 403, 'FORBIDDEN', $message);
    }

    public static function notFoundResponse(Request $request, string $message = 'The requested resource was not found.'): Response
    {
        return self::httpErrorResponse($request, 404, 'NOT_FOUND', $message);
    }

    public static function httpErrorResponse(Request $request, int $status, string $code, string $message): Response
    {
        if ($request->expectsJson()) {
            return Response::error($status, $code, $message);
        }

        $template = match ($status) {
            403 => 'errors.403',
            404 => 'errors.404',
            500 => 'errors.500',
            503 => 'errors.maintenance',
            default => 'errors.500',
        };

        $errorAction = self::errorActionForCurrentUser();

        return Response::html(View::render($template, [
            'title' => match ($status) {
                403 => 'Forbidden',
                404 => 'Not Found',
                500 => 'Server Error',
                503 => 'Maintenance',
                default => 'Error',
            },
            'message' => $message,
            'statusCode' => $status,
            'actionHref' => $errorAction['href'],
            'actionLabel' => $errorAction['label'],
        ]), $status);
    }

    private static function exceptionResponse(Throwable $exception, bool $debug): Response
    {
        if (self::expectsJsonFromGlobals()) {
            return Response::error(
                500,
                'SERVER_ERROR',
                'An unexpected server error occurred.',
                $debug ? ['exception' => $exception->getMessage()] : []
            );
        }

        $errorAction = self::errorActionForCurrentUser();

        return Response::html(View::render('errors.500', [
            'title' => 'Server Error',
            'message' => $debug ? $exception->getMessage() : 'Something went wrong while processing your request.',
            'statusCode' => 500,
            'actionHref' => $errorAction['href'],
            'actionLabel' => $errorAction['label'],
        ]), 500);
    }

    private static function errorActionForCurrentUser(): array
    {
        $user = $_SESSION['auth.user'] ?? null;
        return LandingPage::actionForUser(is_array($user) ? $user : null);
    }

    private static function expectsJsonFromGlobals(): bool
    {
        $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
        $path = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);

        return str_contains($accept, 'application/json') || str_starts_with($path, '/api/');
    }
}

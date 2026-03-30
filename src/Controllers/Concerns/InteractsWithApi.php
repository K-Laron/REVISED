<?php

declare(strict_types=1);

namespace App\Controllers\Concerns;

use App\Core\Request;
use App\Core\Response;
use App\Support\Pagination;

trait InteractsWithApi
{
    protected function validationError(array $errors, string $message = 'The given data was invalid.'): Response
    {
        return Response::error(422, 'VALIDATION_ERROR', $message, $errors);
    }

    protected function currentUser(Request $request): array
    {
        $user = $request->attribute('auth_user', []);

        return is_array($user) ? $user : [];
    }

    protected function currentUserId(Request $request): int
    {
        return (int) ($this->currentUser($request)['id'] ?? 0);
    }

    protected function paginatedSuccess(array $result, int $page, int $perPage, string $message): Response
    {
        return Response::success(
            $result['items'] ?? [],
            $message,
            Pagination::meta($page, $perPage, (int) ($result['total'] ?? 0))
        );
    }

    protected function fileDownloadResponse(
        string $path,
        string $contentType,
        string $filename,
        string $disposition = 'attachment',
        bool $clearOutputBuffer = false
    ): Response {
        if ($clearOutputBuffer && ob_get_level()) {
            ob_clean();
        }

        return new Response(200, (string) file_get_contents($path), [
            'Content-Type' => $contentType,
            'Content-Disposition' => $disposition . '; filename="' . $filename . '"',
        ]);
    }
}

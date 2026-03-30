<?php

declare(strict_types=1);

namespace Tests\Controllers\Concerns;

use App\Controllers\Concerns\InteractsWithApi;
use App\Core\Request;
use App\Core\Response;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class InteractsWithApiTest extends TestCase
{
    public function testValidationErrorReturnsStandardPayload(): void
    {
        $response = $this->subject()->validation(['name' => ['The name field is required.']]);

        self::assertSame(422, $this->responseProperty($response, 'status'));
        self::assertSame(
            [
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'The given data was invalid.',
                    'details' => [
                        'name' => ['The name field is required.'],
                    ],
                ],
            ],
            $this->responsePayload($response)
        );
    }

    public function testCurrentUserHelpersReadAuthUserAttribute(): void
    {
        $request = new Request([], [], [], [], []);
        $request->setAttribute('auth_user', ['id' => 17, 'role_name' => 'admin']);

        self::assertSame(['id' => 17, 'role_name' => 'admin'], $this->subject()->user($request));
        self::assertSame(17, $this->subject()->userId($request));
    }

    public function testPaginatedSuccessBuildsStandardMetaPayload(): void
    {
        $response = $this->subject()->paginated([
            'items' => [['id' => 1]],
            'total' => 41,
        ], 2, 20, 'Records retrieved successfully.');

        self::assertSame(
            [
                'success' => true,
                'data' => [['id' => 1]],
                'meta' => [
                    'page' => 2,
                    'per_page' => 20,
                    'total' => 41,
                    'total_pages' => 3,
                ],
                'message' => 'Records retrieved successfully.',
            ],
            $this->responsePayload($response)
        );
    }

    public function testFileDownloadResponseBuildsExpectedHeaders(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'download-');
        self::assertNotFalse($path);
        file_put_contents($path, 'download body');

        try {
            $response = $this->subject()->download($path, 'text/plain', 'sample.txt', 'inline');
        } finally {
            unlink($path);
        }

        self::assertSame(200, $this->responseProperty($response, 'status'));
        self::assertSame('download body', $this->responseProperty($response, 'content'));
        self::assertSame(
            [
                'Content-Type' => 'text/plain',
                'Content-Disposition' => 'inline; filename="sample.txt"',
            ],
            $this->responseProperty($response, 'headers')
        );
    }

    private function subject(): object
    {
        return new class {
            use InteractsWithApi;

            public function validation(array $errors, string $message = 'The given data was invalid.'): Response
            {
                return $this->validationError($errors, $message);
            }

            public function user(Request $request): array
            {
                return $this->currentUser($request);
            }

            public function userId(Request $request): int
            {
                return $this->currentUserId($request);
            }

            public function paginated(array $result, int $page, int $perPage, string $message): Response
            {
                return $this->paginatedSuccess($result, $page, $perPage, $message);
            }

            public function download(
                string $path,
                string $contentType,
                string $filename,
                string $disposition = 'attachment',
                bool $clearOutputBuffer = false
            ): Response {
                return $this->fileDownloadResponse($path, $contentType, $filename, $disposition, $clearOutputBuffer);
            }
        };
    }

    private function responsePayload(Response $response): array
    {
        return json_decode((string) $this->responseProperty($response, 'content'), true, 512, JSON_THROW_ON_ERROR);
    }

    private function responseProperty(Response $response, string $name): mixed
    {
        $reflection = new ReflectionClass($response);
        $property = $reflection->getProperty($name);

        return $property->getValue($response);
    }
}

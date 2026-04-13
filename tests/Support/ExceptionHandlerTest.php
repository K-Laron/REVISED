<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Core\App;
use App\Core\Container;
use App\Core\ExceptionHandler;
use App\Core\Response;
use RuntimeException;
use Tests\TestCase;

final class ExceptionHandlerTest extends TestCase
{
    protected function tearDown(): void
    {
        restore_exception_handler();
        restore_error_handler();
        Response::resetSentHeaders();
        header_remove();
        http_response_code(200);
        parent::tearDown();
    }

    public function testRegisteredExceptionHandlerRendersServerErrorResponse(): void
    {
        App::setContainer(new Container());
        $_SERVER['REQUEST_URI'] = '/animals';
        $_SERVER['HTTP_ACCEPT'] = 'text/html';

        ExceptionHandler::register(['debug' => false]);

        $handler = set_exception_handler(static function (): void {
        });
        restore_exception_handler();

        self::assertIsCallable($handler);

        ob_start();
        try {
            $handler(new RuntimeException('boom'));
        } finally {
            $content = ob_get_clean() ?: '';
        }

        self::assertSame(500, http_response_code());
        self::assertSame('text/html; charset=utf-8', Response::sentHeaders()['Content-Type'] ?? null);
        self::assertStringContainsString('Server Error', $content);
    }
}

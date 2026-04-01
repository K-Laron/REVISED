<?php

declare(strict_types=1);

namespace Tests\Integration\Http;

use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use PHPUnit\Framework\TestCase;

final class RouterDispatchTest extends TestCase
{
    public function testRouterDispatchesDynamicRouteParametersIntoTheHandler(): void
    {
        $router = new Router();
        $request = new Request(
            [
                'REQUEST_METHOD' => 'GET',
                'REQUEST_URI' => '/tests/router/123',
                'HTTP_ACCEPT' => 'application/json',
            ],
            [],
            [],
            [],
            []
        );

        $router->get('/tests/router/{id}', static function (Request $request, string $id): Response {
            return Response::success([
                'route_param' => $id,
                'request_param' => $request->route('id'),
            ]);
        });

        ob_start();
        http_response_code(200);
        $router->dispatch($request);
        $content = ob_get_clean() ?: '';
        $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(200, http_response_code());
        self::assertSame('123', $decoded['data']['route_param']);
        self::assertSame('123', $decoded['data']['request_param']);
    }
}

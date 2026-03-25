<?php

declare(strict_types=1);

namespace App\Core;

use Closure;
use RuntimeException;

class Router
{
    /**
     * @var array<string, array<int, array<string, mixed>>>
     */
    private array $routes = [];

    public function get(string $path, callable|string $handler, array $middleware = []): void
    {
        $this->addRoute('GET', $path, $handler, $middleware);
    }

    public function post(string $path, callable|string $handler, array $middleware = []): void
    {
        $this->addRoute('POST', $path, $handler, $middleware);
    }

    public function put(string $path, callable|string $handler, array $middleware = []): void
    {
        $this->addRoute('PUT', $path, $handler, $middleware);
    }

    public function patch(string $path, callable|string $handler, array $middleware = []): void
    {
        $this->addRoute('PATCH', $path, $handler, $middleware);
    }

    public function delete(string $path, callable|string $handler, array $middleware = []): void
    {
        $this->addRoute('DELETE', $path, $handler, $middleware);
    }

    public function dispatch(Request $request): void
    {
        $method = $request->method();
        $path = $request->path();

        foreach ($this->routes[$method] ?? [] as $route) {
            $matches = [];

            if (!preg_match($route['pattern'], $path, $matches)) {
                continue;
            }

            $params = [];
            foreach ($route['parameter_names'] as $parameterName) {
                $params[$parameterName] = $matches[$parameterName] ?? null;
            }

            $request->setRouteParams($params);

            $response = $this->runMiddlewarePipeline(
                $route['middleware'],
                $request,
                fn (Request $request): mixed => $this->invokeHandler($route['handler'], $request, $params)
            );

            if ($response instanceof Response) {
                $response->send();
                return;
            }

            if (is_string($response)) {
                Response::html($response)->send();
                return;
            }

            return;
        }

        ExceptionHandler::notFoundResponse($request)->send();
    }

    private function addRoute(string $method, string $path, callable|string $handler, array $middleware): void
    {
        [$pattern, $parameterNames] = $this->compilePath($path);

        $this->routes[$method][] = [
            'path' => $path,
            'pattern' => $pattern,
            'parameter_names' => $parameterNames,
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    /**
     * @return array{0:string,1:array<int,string>}
     */
    private function compilePath(string $path): array
    {
        $parameterNames = [];
        $pattern = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', static function (array $matches) use (&$parameterNames): string {
            $parameterNames[] = $matches[1];
            return '(?P<' . $matches[1] . '>[^/]+)';
        }, $path);

        return ['#^' . $pattern . '$#', $parameterNames];
    }

    private function runMiddlewarePipeline(array $middleware, Request $request, Closure $destination): mixed
    {
        $runner = array_reduce(
            array_reverse($middleware),
            function (Closure $next, string $definition): Closure {
                return function (Request $request) use ($definition, $next): mixed {
                    [$alias, $parameter] = array_pad(explode(':', $definition, 2), 2, null);
                    $middlewareClass = $this->resolveMiddlewareClass($alias);

                    if ($middlewareClass === null) {
                        return $next($request);
                    }

                    $middlewareInstance = new $middlewareClass();

                    if (!method_exists($middlewareInstance, 'handle')) {
                        throw new RuntimeException(sprintf('Middleware [%s] must implement handle().', $middlewareClass));
                    }

                    return $middlewareInstance->handle($request, $next, $parameter);
                };
            },
            $destination
        );

        return $runner($request);
    }

    private function resolveMiddlewareClass(string $alias): ?string
    {
        $aliases = $GLOBALS['app']['middleware_aliases'] ?? [];

        if (isset($aliases[$alias])) {
            return $aliases[$alias];
        }

        return match (true) {
            $alias === 'role' && class_exists(\App\Middleware\RoleMiddleware::class) => \App\Middleware\RoleMiddleware::class,
            $alias === 'perm' && class_exists(\App\Middleware\PermissionMiddleware::class) => \App\Middleware\PermissionMiddleware::class,
            $alias === 'throttle' && class_exists(\App\Middleware\RateLimitMiddleware::class) => \App\Middleware\RateLimitMiddleware::class,
            default => null,
        };
    }

    private function invokeHandler(callable|string $handler, Request $request, array $params): mixed
    {
        if (is_callable($handler)) {
            return $handler($request, ...array_values($params));
        }

        [$controllerClass, $method] = explode('@', $handler);
        $controller = new $controllerClass();

        return $controller->{$method}($request, ...array_values($params));
    }
}

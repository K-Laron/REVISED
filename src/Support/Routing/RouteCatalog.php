<?php

declare(strict_types=1);

namespace App\Support\Routing;

use RuntimeException;
use Throwable;

final class RouteCatalog
{
    /** @var array<string, array<int, array{method:string,path:string,handler:string,middleware:array}>> */
    private static array $memoryCache = [];

    /** @var list<string> */
    private array $entryFiles;

    /** @var list<string> */
    private array $watchFiles;

    public function __construct(
        ?array $entryFiles = null,
        ?array $watchFiles = null,
        ?string $cachePath = null
    ) {
        $projectRoot = dirname(__DIR__, 3);

        $this->entryFiles = $entryFiles ?? [
            $projectRoot . '/routes/web.php',
            $projectRoot . '/routes/api.php',
        ];
        $this->watchFiles = $watchFiles ?? $this->defaultWatchFiles($projectRoot);
        $this->cachePath = $cachePath ?? $projectRoot . '/storage/cache/route_catalog.php';
    }

    private string $cachePath;

    public function register(object $router): void
    {
        foreach ($this->definitions() as $definition) {
            $method = strtolower($definition['method']);

            if (!method_exists($router, $method)) {
                throw new RuntimeException('Router does not support [' . $definition['method'] . '] route registration.');
            }

            $router->{$method}($definition['path'], $definition['handler'], $definition['middleware']);
        }
    }

    /**
     * @return list<array{method:string,path:string,handler:string,middleware:array}>
     */
    public function definitions(): array
    {
        $signature = $this->sourceSignature();
        $memoryKey = $this->cachePath . ':' . sha1(json_encode($signature, JSON_THROW_ON_ERROR));

        if (isset(self::$memoryCache[$memoryKey])) {
            return self::$memoryCache[$memoryKey];
        }

        $cached = $this->readCache();
        if (
            isset($cached['signature'], $cached['routes'])
            && $cached['signature'] === $signature
            && is_array($cached['routes'])
        ) {
            return self::$memoryCache[$memoryKey] = $cached['routes'];
        }

        $definitions = $this->buildDefinitions();
        $this->writeCache([
            'signature' => $signature,
            'routes' => $definitions,
        ]);

        return self::$memoryCache[$memoryKey] = $definitions;
    }

    /**
     * @return list<string>
     */
    private function defaultWatchFiles(string $projectRoot): array
    {
        $files = [
            $projectRoot . '/routes/web.php',
            $projectRoot . '/routes/api.php',
        ];

        $apiModules = glob($projectRoot . '/routes/api/*.php') ?: [];

        $files = array_merge($files, $apiModules);
        $files = array_values(array_unique($files));
        sort($files);

        return $files;
    }

    /**
     * @return array{debug:bool,files:array<string,array{mtime:int|false,size:int|false}>}
     */
    private function sourceSignature(): array
    {
        $files = [];

        foreach ($this->watchFiles as $path) {
            $files[$path] = [
                'mtime' => is_file($path) ? filemtime($path) : false,
                'size' => is_file($path) ? filesize($path) : false,
            ];
        }

        ksort($files);

        return [
            'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOL),
            'files' => $files,
        ];
    }

    /**
     * @return list<array{method:string,path:string,handler:string,middleware:array}>
     */
    private function buildDefinitions(): array
    {
        $recorder = new RouteDefinitionRecorder();
        $router = $recorder;

        foreach ($this->entryFiles as $file) {
            if (!is_file($file)) {
                throw new RuntimeException('Route entry file not found: ' . $file);
            }

            require $file;
        }

        return $recorder->definitions();
    }

    private function readCache(): array
    {
        if (!is_file($this->cachePath)) {
            return [];
        }

        try {
            $cache = require $this->cachePath;
        } catch (Throwable) {
            return [];
        }

        return is_array($cache) ? $cache : [];
    }
    private function writeCache(array $payload): void
    {
        $directory = dirname($this->cachePath);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('Failed to create the route cache directory.');
        }

        $exported = var_export($payload, true);
        $contents = "<?php\n\ndeclare(strict_types=1);\n\nreturn " . $exported . ";\n";

        if (file_put_contents($this->cachePath, $contents) === false) {
            throw new RuntimeException('Failed to write the route cache.');
        }
    }
}

final class RouteDefinitionRecorder
{
    /** @var list<array{method:string,path:string,handler:string,middleware:array}> */
    private array $definitions = [];

    public function get(string $path, callable|string $handler, array $middleware = []): void
    {
        $this->record('GET', $path, $handler, $middleware);
    }

    public function post(string $path, callable|string $handler, array $middleware = []): void
    {
        $this->record('POST', $path, $handler, $middleware);
    }

    public function put(string $path, callable|string $handler, array $middleware = []): void
    {
        $this->record('PUT', $path, $handler, $middleware);
    }

    public function patch(string $path, callable|string $handler, array $middleware = []): void
    {
        $this->record('PATCH', $path, $handler, $middleware);
    }

    public function delete(string $path, callable|string $handler, array $middleware = []): void
    {
        $this->record('DELETE', $path, $handler, $middleware);
    }

    /**
     * @return list<array{method:string,path:string,handler:string,middleware:array}>
     */
    public function definitions(): array
    {
        return $this->definitions;
    }

    private function record(string $method, string $path, callable|string $handler, array $middleware): void
    {
        if (!is_string($handler)) {
            throw new RuntimeException('Route caching only supports string handlers.');
        }

        $this->definitions[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }
}

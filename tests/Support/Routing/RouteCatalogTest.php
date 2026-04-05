<?php

declare(strict_types=1);

namespace Tests\Support\Routing;

use App\Support\Routing\RouteCatalog;
use PHPUnit\Framework\TestCase;

final class RouteCatalogTest extends TestCase
{
    private string $workspace;
    private string $cachePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workspace = sys_get_temp_dir() . '/route-catalog-test-' . bin2hex(random_bytes(8));
        $this->cachePath = $this->workspace . '/route-catalog.php';

        mkdir($this->workspace, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory($this->workspace);

        parent::tearDown();
    }

    public function testCatalogBuildsAndRegistersRoutesFromEntryFiles(): void
    {
        $webRoutes = $this->workspace . '/web.php';
        $apiRoutes = $this->workspace . '/api.php';

        file_put_contents($webRoutes, <<<'PHP'
<?php
$router->get('/dashboard', 'DashboardController@index', ['auth']);
$router->post('/animals', 'AnimalController@store', ['auth', 'csrf']);
PHP);

        file_put_contents($apiRoutes, <<<'PHP'
<?php
$router->get('/api/ping', 'SystemController@ping', ['cors']);
PHP);

        $catalog = new RouteCatalog([$webRoutes, $apiRoutes], [$webRoutes, $apiRoutes], $this->cachePath);

        self::assertSame([
            ['method' => 'GET', 'path' => '/dashboard', 'handler' => 'DashboardController@index', 'middleware' => ['auth']],
            ['method' => 'POST', 'path' => '/animals', 'handler' => 'AnimalController@store', 'middleware' => ['auth', 'csrf']],
            ['method' => 'GET', 'path' => '/api/ping', 'handler' => 'SystemController@ping', 'middleware' => ['cors']],
        ], $catalog->definitions());

        $router = new class {
            public array $routes = [];

            public function get(string $path, callable|string $handler, array $middleware = []): void
            {
                $this->routes[] = ['method' => 'GET', 'path' => $path, 'handler' => $handler, 'middleware' => $middleware];
            }

            public function post(string $path, callable|string $handler, array $middleware = []): void
            {
                $this->routes[] = ['method' => 'POST', 'path' => $path, 'handler' => $handler, 'middleware' => $middleware];
            }

            public function put(string $path, callable|string $handler, array $middleware = []): void
            {
                $this->routes[] = ['method' => 'PUT', 'path' => $path, 'handler' => $handler, 'middleware' => $middleware];
            }

            public function patch(string $path, callable|string $handler, array $middleware = []): void
            {
                $this->routes[] = ['method' => 'PATCH', 'path' => $path, 'handler' => $handler, 'middleware' => $middleware];
            }

            public function delete(string $path, callable|string $handler, array $middleware = []): void
            {
                $this->routes[] = ['method' => 'DELETE', 'path' => $path, 'handler' => $handler, 'middleware' => $middleware];
            }
        };

        $catalog->register($router);

        self::assertSame($catalog->definitions(), $router->routes);
        self::assertFileExists($this->cachePath);
    }

    public function testCatalogInvalidatesCacheWhenWatchedFilesChange(): void
    {
        $entryFile = $this->workspace . '/routes.php';
        file_put_contents($entryFile, <<<'PHP'
<?php
$router->get('/first', 'FirstController@index');
PHP);

        $catalog = new RouteCatalog([$entryFile], [$entryFile], $this->cachePath);

        self::assertSame('/first', $catalog->definitions()[0]['path']);

        sleep(1);
        file_put_contents($entryFile, <<<'PHP'
<?php
$router->get('/second', 'SecondController@index');
PHP);

        $reloaded = new RouteCatalog([$entryFile], [$entryFile], $this->cachePath);

        self::assertSame('/second', $reloaded->definitions()[0]['path']);
    }

    private function deleteDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = scandir($path);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $itemPath = $path . '/' . $item;
            if (is_dir($itemPath)) {
                $this->deleteDirectory($itemPath);
                continue;
            }

            unlink($itemPath);
        }

        rmdir($path);
    }
}

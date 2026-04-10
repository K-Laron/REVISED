<?php

declare(strict_types=1);

namespace Tests\Integration;

require_once __DIR__ . '/Support/DatabaseFixtureFactory.php';

use App\Core\Database;
use App\Core\Request;
use Tests\TestCase;
use Tests\Integration\Support\DatabaseFixtureFactory;
use Throwable;

abstract class DatabaseIntegrationTestCase extends TestCase
{
    private static bool $databaseReady = false;
    private static ?string $databaseError = null;

    /** @var string[] */
    private array $trackedFiles = [];

    private ?DatabaseFixtureFactory $fixtures = null;

    protected function setUp(): void
    {
        parent::setUp();

        // For integration tests, we want a REAL database connection, not a mock.
        // We re-bind the REAL Database service into the container.
        $this->container->singleton(Database::class, function () {
            return new Database();
        });

        if (!self::$databaseReady) {
            try {
                Database::fetch('SELECT 1 AS ok');
                self::$databaseReady = true;
                self::$databaseError = null;
            } catch (Throwable $exception) {
                self::$databaseReady = false;
                self::$databaseError = $exception->getMessage();
            }
        }

        if (!self::$databaseReady) {
            self::markTestSkipped('Database integration test skipped: ' . (self::$databaseError ?? 'Database unavailable.'));
        }

        Database::beginTransaction();
    }

    protected function tearDown(): void
    {
        foreach (array_reverse($this->trackedFiles) as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }

        Database::rollBack();
        parent::tearDown();
    }

    protected function makeRequest(
        array $body = [],
        array $query = [],
        array $files = [],
        array $attributes = [],
        array $server = []
    ): Request {
        $request = new Request(
            array_merge([
                'REQUEST_METHOD' => 'POST',
                'REQUEST_URI' => '/tests/integration',
                'REMOTE_ADDR' => '127.0.0.1',
                'HTTP_USER_AGENT' => 'PHPUnit Integration Test',
                'HTTP_ACCEPT' => 'application/json',
            ], $server),
            $query,
            $body,
            $files,
            []
        );

        foreach ($attributes as $key => $value) {
            $request->setAttribute($key, $value);
        }

        return $request;
    }

    protected function trackRelativePath(?string $relativePath): void
    {
        if ($relativePath === null || $relativePath === '') {
            return;
        }

        $this->trackedFiles[] = $this->absolutePathFor($relativePath);
    }

    protected function absolutePathFor(string $relativePath): string
    {
        $normalizedPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);
        $root = dirname(__DIR__, 2);

        if (str_starts_with($normalizedPath, 'uploads' . DIRECTORY_SEPARATOR)) {
            return $root . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . $normalizedPath;
        }

        return $root . DIRECTORY_SEPARATOR . $normalizedPath;
    }

    protected function roleId(string $roleName): int
    {
        return $this->fixtureFactory()->roleId($roleName);
    }

    protected function createUser(string $roleName, array $overrides = []): array
    {
        return $this->fixtureFactory()->createUser($roleName, $overrides);
    }

    protected function createAnimal(array $overrides = []): array
    {
        return $this->fixtureFactory()->createAnimal($overrides);
    }

    protected function createApplication(array $overrides = []): array
    {
        return $this->fixtureFactory()->createApplication($overrides);
    }

    protected function createSeminar(array $overrides = []): array
    {
        return $this->fixtureFactory()->createSeminar($overrides);
    }

    protected function createInvoice(array $overrides = []): array
    {
        return $this->fixtureFactory()->createInvoice($overrides);
    }

    private function fixtureFactory(): DatabaseFixtureFactory
    {
        if ($this->fixtures === null) {
            $this->fixtures = new DatabaseFixtureFactory();
        }

        return $this->fixtures;
    }
}

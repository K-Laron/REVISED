<?php

declare(strict_types=1);

namespace Tests;

use App\Core\App;
use App\Core\Container;
use App\Core\Session;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected ?Container $container = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Initialize a clean container for each test
        $this->container = new Container();
        App::setContainer($this->container);

        // Bind a mock-friendly Session by default
        $this->setUpSession();

        // Bind ImageManager as null for tests (GD may not be available)
        $this->container->singleton(\Intervention\Image\ImageManager::class, fn() => null);

        // Bind SearchService and SearchModuleCatalog with providers array
        $this->container->singleton(\App\Services\Search\SearchModuleCatalog::class, function(\App\Core\Container $c) {
            return new \App\Services\Search\SearchModuleCatalog([
                $c->get(\App\Services\Search\Providers\AnimalsSearchProvider::class),
                $c->get(\App\Services\Search\Providers\AdoptionsSearchProvider::class),
                $c->get(\App\Services\Search\Providers\MedicalSearchProvider::class),
                $c->get(\App\Services\Search\Providers\BillingSearchProvider::class),
                $c->get(\App\Services\Search\Providers\InventorySearchProvider::class),
                $c->get(\App\Services\Search\Providers\UsersSearchProvider::class),
            ]);
        });

        $this->container->singleton(\App\Services\SearchService::class, function(\App\Core\Container $c) {
            return new \App\Services\SearchService(
                [
                    $c->get(\App\Services\Search\Providers\AnimalsSearchProvider::class),
                    $c->get(\App\Services\Search\Providers\AdoptionsSearchProvider::class),
                    $c->get(\App\Services\Search\Providers\MedicalSearchProvider::class),
                    $c->get(\App\Services\Search\Providers\BillingSearchProvider::class),
                    $c->get(\App\Services\Search\Providers\InventorySearchProvider::class),
                    $c->get(\App\Services\Search\Providers\UsersSearchProvider::class),
                ],
                $c->get(\App\Services\Search\SearchFilterCatalog::class)
            );
        });
    }

    protected function tearDown(): void
    {
        // Reset the global container to prevent state leakage between tests
        App::setContainer(new Container());
        $this->container = null;

        parent::tearDown();
    }

    private function setUpSession(): void
    {
        // Use a mock session that interacts with $_SESSION global for compatibility
        $mockSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockSession->method('instanceGet')->willReturnCallback(function($key, $default = null) {
            return $_SESSION[$key] ?? $default;
        });

        $mockSession->method('instancePut')->willReturnCallback(function($key, $value) {
            $_SESSION[$key] = $value;
        });

        $mockSession->method('instanceForget')->willReturnCallback(function($key) {
            unset($_SESSION[$key]);
        });

        $mockSession->method('instanceDestroy')->willReturnCallback(function() {
            $_SESSION = [];
        });

        $mockSession->method('instanceRegenerate')->willReturn(true);

        $this->container->singleton(Session::class, function() use ($mockSession) {
            return $mockSession;
        });
    }
}

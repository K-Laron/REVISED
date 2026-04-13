<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Core\App;
use App\Core\Container;
use App\Services\Animal\AnimalPhotoManager;
use Tests\TestCase;

final class AppBootstrapTest extends TestCase
{
    public function testBootstrapAppLoadsConfigurationAndSystemSettings(): void
    {
        App::setContainer(new Container());

        $config = require dirname(__DIR__, 2) . '/bootstrap/app.php';

        self::assertIsArray($config);
        self::assertArrayHasKey('settings', $config);
        self::assertIsArray($config['settings']);
        self::assertArrayHasKey('app_name', $config['settings']);
    }

    public function testBootstrapCanResolveAnimalPhotoManagerWithoutImageDriver(): void
    {
        App::setContainer(new Container());

        require dirname(__DIR__, 2) . '/bootstrap/app.php';
        restore_exception_handler();
        restore_error_handler();

        $manager = App::make(AnimalPhotoManager::class);

        self::assertInstanceOf(AnimalPhotoManager::class, $manager);
    }
}

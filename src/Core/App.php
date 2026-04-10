<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Global App bridge for accessing the Service Container and core instances.
 */
class App
{
    private static ?Container $container = null;

    /**
     * Get the global container instance.
     *
     * @return Container
     */
    public static function container(): Container
    {
        if (self::$container === null) {
            self::$container = new Container();
        }

        return self::$container;
    }

    /**
     * Set the global container instance.
     *
     * @param Container $container
     */
    public static function setContainer(Container $container): void
    {
        self::$container = $container;
    }

    /**
     * Static helper to resolve a class from the container.
     *
     * @template T
     * @param class-string<T> $abstract
     * @return T
     */
    public static function make(string $abstract): mixed
    {
        return self::container()->get($abstract);
    }
}

<?php

declare(strict_types=1);

namespace App\Core;

use Exception;
use ReflectionClass;
use ReflectionParameter;

class Container
{
    /**
     * @var array<string, mixed>
     */
    private array $instances = [];

    /**
     * @var array<string, callable>
     */
    private array $bindings = [];

    /**
     * Register a class or interface binding.
     *
     * @param string $abstract
     * @param callable|string|null $concrete
     */
    public function bind(string $abstract, mixed $concrete = null): void
    {
        if ($concrete === null) {
            $concrete = $abstract;
        }

        $this->bindings[$abstract] = $concrete;
    }

    /**
     * Register a shared singleton instance.
     *
     * @param string $abstract
     * @param mixed $concrete
     */
    public function singleton(string $abstract, mixed $concrete = null): void
    {
        $this->bind($abstract, $concrete);
    }

    /**
     * Resolve the given type from the container.
     *
     * @param string $abstract
     * @return mixed
     * @throws Exception
     */
    public function get(string $abstract): mixed
    {
        // Return existing instance if available
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        $concrete = $this->bindings[$abstract] ?? $abstract;

        // If the concrete is already an object, return it (e.g., a mock or pre-instantiated service)
        if (is_object($concrete) && !($concrete instanceof \Closure)) {
            $this->instances[$abstract] = $concrete;
            return $concrete;
        }

        // Handle closure/callable bindings
        if (is_callable($concrete)) {
            $instance = $concrete($this);
        } else {
            $instance = $this->resolve($concrete);
        }

        // Store as singleton if requested (for this custom implementation, everything resolved via get is treated as a potential singleton if not a factory)
        // For now, we'll follow a simple "resolve once, reuse" pattern for services.
        $this->instances[$abstract] = $instance;

        return $instance;
    }

    /**
     * Determine if the given abstract type has been bound.
     *
     * @param string $abstract
     * @return bool
     */
    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || class_exists($abstract);
    }

    /**
     * Instantiate a concrete instance of the given type.
     *
     * @param string $concrete
     * @return mixed
     * @throws Exception
     */
    private function resolve(string $concrete): mixed
    {
        $reflection = new ReflectionClass($concrete);

        if (!$reflection->isInstantiable()) {
            throw new Exception("Class {$concrete} is not instantiable.");
        }

        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return new $concrete();
        }

        $parameters = $constructor->getParameters();
        $dependencies = $this->resolveDependencies($parameters);

        return $reflection->newInstanceArgs($dependencies);
    }

    /**
     * Resolve constructor dependencies.
     *
     * @param ReflectionParameter[] $parameters
     * @return array
     * @throws Exception
     */
    private function resolveDependencies(array $parameters): array
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();

            if ($type === null) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                    continue;
                }
                throw new Exception("Unable to resolve dependency [{$parameter->name}] in class {$parameter->getDeclaringClass()->getName()}");
            }

            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                $dependencies[] = $this->get($type->getName());
            } else {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                } else {
                    throw new Exception("Unable to resolve primitive dependency [{$parameter->name}] in class {$parameter->getDeclaringClass()->getName()}");
                }
            }
        }

        return $dependencies;
    }
}

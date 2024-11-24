<?php

declare(strict_types=1);

namespace App\Shared\Services;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Qubus\Inheritance\StaticProxyAware;

final class Registry implements ContainerInterface
{
    use StaticProxyAware;

    protected array $container = [];

    /**
     * Retrieves a sub-key from the registry.
     *
     * @param string $object name of an object to retrieve a key from.
     * @param bool $key (optional) key to retrieve from the object.
     * @param mixed|null $default (optional) default value for missing objects or keys.
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function prop(string $object, bool $key = false, mixed $default = null): mixed
    {
        if ($obj = $this->get($object)) {
            return ($key !== false ? $obj->{$key} : $obj);
        }
        return $default;
    }

    /**
     * @inheritDoc
     */
    public function get(string $id)
    {
        return $this->container[$id];
    }

    /**
     * @inheritDoc
     */
    public function has(string $id): bool
    {
        return array_key_exists($id, $this->container);
    }

    /**
     * Sets a registry parameter.
     *
     * If a registry parameter with the name already exists the value will be overridden.
     *
     * @param string $id   A registry parameter name.
     * @param mixed $value A registry parameter value
     */
    public function set(string $id, mixed $value): void
    {
        $this->container[$id] = $value;
    }

    /**
     * Sets an array of registry parameters.
     *
     * If an existing registry parameter name matches any of the keys in the supplied
     * array, the associated value will be overridden.
     *
     * @param array $parameters An associative array of registry parameters and their associated values.
     */
    public function add(array $parameters = []): void
    {
        $this->container = array_merge($this->container, $parameters);
    }

    /**
     * Retrieves all configuration parameters.
     *
     * @return array An associative array of configuration parameters.
     */
    public function getAll(): array
    {
        return $this->container;
    }

    /**
     * Clears all current container parameters.
     */
    public function clear(): void
    {
        $this->container = [];
    }
}
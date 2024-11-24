<?php

declare(strict_types=1);

namespace App\Shared\Services;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Qubus\Cache\Psr6\ItemPool;
use ReflectionException;

use function Codefy\Framework\Helpers\config;

final class ItemPoolObjectCacheFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     * @throws NotFoundExceptionInterface
     */
    public static function make(string $namespace = 'default'): CacheItemPoolInterface
    {
        return new ItemPool(
            adapter: Registry::getInstance()->get('cacheAdapter'),
            ttl: config(key: 'cache.ttl'),
            namespace: $namespace
        );
    }
}

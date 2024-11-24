<?php

declare(strict_types=1);

namespace App\Shared\Services;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\CacheInterface;
use Qubus\Cache\Psr16\SimpleCache;
use ReflectionException;

use function Codefy\Framework\Helpers\config;

final class SimpleCacheObjectCacheFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     * @throws NotFoundExceptionInterface
     */
    public static function make(string $namespace = 'default'): CacheInterface
    {
        return new SimpleCache(
            adapter: Registry::getInstance()->get('cacheAdapter'),
            ttl: config(key: 'cache.ttl'),
            namespace: $namespace
        );
    }
}

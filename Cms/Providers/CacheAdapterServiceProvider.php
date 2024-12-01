<?php

declare(strict_types=1);

namespace Cms\Providers;

use App\Shared\Services\Registry;
use Codefy\Framework\Support\CodefyServiceProvider;
use Codefy\Framework\Support\LocalStorage;
use Qubus\Cache\Adapter\CacheAdapter;
use Qubus\Cache\Adapter\FileSystemCacheAdapter;
use ReflectionException;

class CacheAdapterServiceProvider extends CodefyServiceProvider
{
    /**
     * @throws ReflectionException
     */
    public function register(): void
    {
        /*if ($this->codefy->isRunningInConsole()) {
            return;
        }*/

        $this->codefy->alias(original: CacheAdapter::class, alias: FileSystemCacheAdapter::class);
        $this->codefy->define(
            name: FileSystemCacheAdapter::class,
            args: [':operator' => LocalStorage::disk(name: 'cache')]
        );

        $this->codefy->share(nameOrInstance: CacheAdapter::class);


        /** Do not touch or you will break the system. */
        Registry::getInstance()->set('cacheAdapter', $this->codefy->make(name: CacheAdapter::class));
    }
}

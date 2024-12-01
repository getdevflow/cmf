<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Cache;

use App\Domain\Site\Model\Site;
use App\Domain\Site\Services\SiteCache;
use App\Shared\Services\SimpleCacheObjectCacheFactory;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Qubus\EventDispatcher\ActionFilter\Action;
use Qubus\Exception\Exception;
use ReflectionException;

use function md5;

class SiteCachePsr16 implements SiteCache
{
    /**
     * @inheritDoc
     * @param Site|array $site
     * @throws InvalidArgumentException
     * @throws ReflectionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function update(Site|array $site): void
    {
        if ($site instanceof Site) {
            $site = $site->toArray();
        }

        if (empty($site)) {
            return;
        }

        SimpleCacheObjectCacheFactory::make(namespace: 'sites')->set(md5($site['id']), $site);
        SimpleCacheObjectCacheFactory::make(namespace: 'sitekey')->set(md5($site['key']), $site['id']);
        SimpleCacheObjectCacheFactory::make(namespace: 'siteslug')->set(md5($site['slug']), $site['id']);
    }

    /**
     * @inheritDoc
     * @param Site|array $site
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public static function clean(Site|array $site): void
    {
        if ($site instanceof Site) {
            $site = $site->toArray();
        }

        if (empty($site)) {
            return;
        }

        SimpleCacheObjectCacheFactory::make(namespace: 'sites')->delete(md5($site['id']));
        SimpleCacheObjectCacheFactory::make(namespace: 'sitekey')->delete(md5($site['key']));
        SimpleCacheObjectCacheFactory::make(namespace: 'siteslug')->delete(md5($site['slug']));

        /**
         * Fires immediately after the given site's cache is cleaned.
         *
         * @param string $siteId Site id.
         * @param array  $site   Site array.
         */
        Action::getInstance()->doAction('clean_site_cache', $site['id'], $site);
    }
}

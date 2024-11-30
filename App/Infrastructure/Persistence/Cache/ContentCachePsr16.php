<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Cache;

use App\Domain\Content\Model\Content;
use App\Domain\Content\Services\ContentCache;
use App\Shared\Services\SimpleCacheObjectCacheFactory;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Qubus\EventDispatcher\ActionFilter\Action;
use Qubus\Exception\Exception;
use ReflectionException;

use function App\Shared\Helpers\dfdb;
use function md5;

class ContentCachePsr16 implements ContentCache
{
    /**
     * @inheritDoc
     * @param Content|array $content
     * @throws InvalidArgumentException
     * @throws ReflectionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function update(Content|array $content): void
    {
        if ($content instanceof Content) {
            $content = $content->toArray();
        }

        if (empty($content)) {
            return;
        }

        SimpleCacheObjectCacheFactory::make(namespace: dfdb()->prefix . 'content')
                ->set(md5($content['id']), $content);

        SimpleCacheObjectCacheFactory::make(namespace: dfdb()->prefix . 'contentslug')
                ->set(md5($content['slug']), $content['id']);

        SimpleCacheObjectCacheFactory::make(namespace: dfdb()->prefix . 'contenttype')
                ->set(md5($content['type']), $content['id']);
    }

    /**
     * @inheritDoc
     * @param Content|array $content
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public static function clean(Content|array $content): void
    {
        if ($content instanceof Content) {
            $content = $content->toArray();
        }

        if (empty($content)) {
            return;
        }

        SimpleCacheObjectCacheFactory::make(namespace: dfdb()->prefix . 'content')
                ->delete(md5($content['id']));

        SimpleCacheObjectCacheFactory::make(namespace: dfdb()->prefix . 'contentslug')
                ->delete(md5($content['slug']));

        SimpleCacheObjectCacheFactory::make(namespace: dfdb()->prefix . 'contenttype')
                ->delete(md5($content['type']));

        SimpleCacheObjectCacheFactory::make(namespace: dfdb()->prefix . 'contentmeta')
                ->delete(md5($content['id']));

        /**
         * Fires immediately after the given user's cache is cleaned.
         *
         * @param string $contentId Content id.
         * @param array  $content   Content array.
         */
        Action::getInstance()->doAction('clean_content_cache', $content['id'], $content);
    }
}

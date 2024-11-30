<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Cache;

use App\Domain\Product\Model\Product;
use App\Domain\Product\Service\ProductCache;
use App\Shared\Services\SimpleCacheObjectCacheFactory;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Qubus\EventDispatcher\ActionFilter\Action;
use Qubus\Exception\Exception;
use ReflectionException;

use function App\Shared\Helpers\dfdb;
use function md5;

class ProductCachePsr16 implements ProductCache
{
    /**
     * @inheritDoc
     * @param Product|array $product
     * @throws InvalidArgumentException
     * @throws ReflectionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function update(Product|array $product): void
    {
        if ($product instanceof Product) {
            $product = $product->toArray();
        }

        if (empty($product)) {
            return;
        }

        SimpleCacheObjectCacheFactory::make(namespace: dfdb()->prefix . 'products')
                ->set(md5($product['id']), $product);

        SimpleCacheObjectCacheFactory::make(namespace: dfdb()->prefix . 'productslug')
                ->set(md5($product['slug']), $product['id']);

        SimpleCacheObjectCacheFactory::make(namespace: dfdb()->prefix . 'productsku')
                ->set(md5($product['sku']), $product['id']);
    }

    /**
     * @inheritDoc
     * @param Product|array $product
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public static function clean(Product|array $product): void
    {
        if ($product instanceof Product) {
            $product = $product->toArray();
        }

        if (empty($product)) {
            return;
        }

        SimpleCacheObjectCacheFactory::make(namespace: dfdb()->prefix . 'products')
                ->delete(md5($product['id']));

        SimpleCacheObjectCacheFactory::make(namespace: dfdb()->prefix . 'productslug')
                ->delete(md5($product['slug']));

        SimpleCacheObjectCacheFactory::make(namespace: dfdb()->prefix . 'productsku')
                ->delete(md5($product['sku']));

        SimpleCacheObjectCacheFactory::make(namespace: dfdb()->prefix . 'productmeta')
                ->delete(md5($product['id']));

        /**
         * Fires immediately after the given user's cache is cleaned.
         *
         * @param string $productId Product id.
         * @param array  $product   Product array.
         */
        Action::getInstance()->doAction('clean_product_cache', $product['id'], $product);
    }
}

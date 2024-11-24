<?php

declare(strict_types=1);

namespace App\Domain\Product\Service;

use App\Domain\Product\Model\Product;

interface ProductCache
{
    /**
     * Update user caches.
     *
     * @param Product|array $product Product object or product array to be cached.
     */
    public static function update(Product|array $product): void;

    /**
     * Clean user caches.
     *
     * @param Product|array $product Product object or product array to be cleaned from the cache.
     */
    public static function clean(Product|array $product): void;
}

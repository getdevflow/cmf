<?php

declare(strict_types=1);

namespace App\Domain\Content\Services;

use App\Domain\Content\Model\Content;

interface ContentCache
{
    /**
     * Update user caches.
     *
     * @param Content|array $content Content object or content array to be cached.
     */
    public static function update(Content|array $content): void;

    /**
     * Clean user caches.
     *
     * @param Content|array $content Content object or content array to be cleaned from the cache.
     */
    public static function clean(Content|array $content): void;
}

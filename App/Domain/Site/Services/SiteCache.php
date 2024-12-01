<?php

declare(strict_types=1);

namespace App\Domain\Site\Services;

use App\Domain\Site\Model\Site;

interface SiteCache
{
    /**
     * Update site caches.
     *
     * @param Site|array $site Site object or site array to be cached.
     */
    public static function update(Site|array $site): void;

    /**
     * Clean site caches.
     *
     * @param Site|array $site Site object or site array to be cleaned from the cache.
     */
    public static function clean(Site|array $site): void;
}

<?php

declare(strict_types=1);

namespace App\Domain\User\Services;

use App\Domain\User\Model\User;

interface UserCache
{
    /**
     * Update user caches.
     *
     * @param User|array $user User object or user array to be cached.
     */
    public static function update(User|array $user): void;

    /**
     * Clean user caches.
     *
     * @param User|array $user User object or user array to be cleaned from the cache.
     */
    public static function clean(User|array $user): void;
}

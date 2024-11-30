<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Cache;

use App\Domain\User\Model\User;
use App\Domain\User\Services\UserCache;
use App\Shared\Services\SimpleCacheObjectCacheFactory;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Qubus\EventDispatcher\ActionFilter\Action;
use Qubus\Exception\Exception;
use ReflectionException;

use function App\Shared\Helpers\dfdb;
use function md5;

class UserCachePsr16 implements UserCache
{
    /**
     * @inheritDoc
     * @param User|array $user
     * @throws InvalidArgumentException
     * @throws ReflectionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function update(User|array $user): void
    {
        if ($user instanceof User) {
            $user = $user->toArray();
        }

        if (empty($user)) {
            return;
        }

        SimpleCacheObjectCacheFactory::make(namespace: 'users')->set(md5($user['id']), $user);
        SimpleCacheObjectCacheFactory::make(namespace: 'userlogin')->set(md5($user['login']), $user['id']);
        SimpleCacheObjectCacheFactory::make(namespace: 'useremail')->set(md5($user['email']), $user['id']);
        SimpleCacheObjectCacheFactory::make(namespace: 'usertoken')->set(md5($user['token']), $user['id']);
    }

    /**
     * @inheritDoc
     * @param User|array $user
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public static function clean(User|array $user): void
    {
        if ($user instanceof User) {
            $user = $user->toArray();
        }

        if (empty($user)) {
            return;
        }

        SimpleCacheObjectCacheFactory::make(namespace: 'users')->delete(md5($user['id']));
        SimpleCacheObjectCacheFactory::make(namespace: 'userlogin')->delete(md5($user['login']));
        SimpleCacheObjectCacheFactory::make(namespace: 'useremail')->delete(md5($user['email']));
        SimpleCacheObjectCacheFactory::make(namespace: 'usertoken')->delete(md5($user['token']));
        SimpleCacheObjectCacheFactory::make(namespace: dfdb()->prefix . 'usermeta')->delete(md5($user['id']));

        /**
         * Fires immediately after the given user's cache is cleaned.
         *
         * @param string $userId User id.
         * @param array  $user   User array.
         */
        Action::getInstance()->doAction('clean_user_cache', $user['id'], $user);
    }
}

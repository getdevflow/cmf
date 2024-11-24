<?php

declare(strict_types=1);

namespace App\Infrastructure\Providers;

use App\Infrastructure\Persistence\FileResource;
use App\Infrastructure\Persistence\Repository\AuthenticationRepository;
use App\Infrastructure\Services\RbacLoader;
use App\Shared\Services\Registry;
use Codefy\Framework\Auth\Auth;
use Codefy\Framework\Auth\Rbac\Rbac;
use Codefy\Framework\Auth\Rbac\Resource\StorageResource;
use Codefy\Framework\Auth\Repository\AuthUserRepository;
use Codefy\Framework\Auth\Sentinel;
use Codefy\Framework\Support\CodefyServiceProvider;
use Qubus\Exception\Exception;
use ReflectionException;

final class RbacServiceProvider extends CodefyServiceProvider
{
    /**
     * @throws Exception|ReflectionException
     */
    public function register(): void
    {
        if ($this->codefy->isRunningInConsole()) {
            return;
        }

        $this->codefy->alias(original: StorageResource::class, alias: FileResource::class);
        $this->codefy->define(name: FileResource::class, args: [
            ':file' => 'rbac.json'
        ]);
        $this->codefy->share(nameOrInstance: StorageResource::class);

        $this->codefy->share(nameOrInstance: Rbac::class);

        /** @var RbacLoader $loader */
        $loader = $this->codefy->make(name: RbacLoader::class);
        $loader->initRbacPermissions();
        $loader->initRbacRoles();

        Registry::getInstance()->set('rbac', $this->codefy->make(name: Rbac::class));

        $this->codefy->alias(original: Sentinel::class, alias: Auth::class);
        $this->codefy->share(nameOrInstance: Sentinel::class);
        $this->codefy->alias(original: AuthUserRepository::class, alias: AuthenticationRepository::class);
        $this->codefy->share(nameOrInstance: AuthUserRepository::class);
    }
}

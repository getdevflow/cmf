<?php

declare(strict_types=1);

namespace App\Infrastructure\Providers;

use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Persistence\NativePdoDatabase;
use App\Shared\Services\Registry;
use Codefy\Framework\Support\CodefyServiceProvider;
use ReflectionException;

class DatabaseServiceProvider extends CodefyServiceProvider
{
    /**
     * @throws ReflectionException
     */
    public function register(): void
    {
        $this->codefy->alias(original: Database::class, alias: NativePdoDatabase::class);
        $this->codefy->share(nameOrInstance: Database::class);

        /** Do not touch. */
        Registry::getInstance()->set('dfdb', $this->codefy->make(Database::class));
    }
}

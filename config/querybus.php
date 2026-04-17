<?php

declare(strict_types=1);

use App\Application\Devflow;
use App\Domain\Content\Repository\ContentQueryRepository;
use App\Domain\ContentType\Repository\ContentTypeQueryRepository;
use App\Domain\Product\Repository\ProductQueryRepository;
use App\Domain\Site\Repository\SitesQueryRepository;
use App\Domain\User\Repository\UserQueryRepository;
use Qubus\Expressive\Database;
use App\Infrastructure\Persistence\NativePdoDatabase;
use App\Infrastructure\Persistence\Repository\QueryBusContentRepository;
use App\Infrastructure\Persistence\Repository\QueryBusContentTypeRepository;
use App\Infrastructure\Persistence\Repository\QueryBusProductRepository;
use App\Infrastructure\Persistence\Repository\QueryBusSitesRepository;
use App\Infrastructure\Persistence\Repository\QueryBusUserRepository;
use Codefy\CommandBus\Container;
use Codefy\CommandBus\Containers\InjectorContainer;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Qubus\Config\Collection;
use Qubus\Config\ConfigContainer;
use Qubus\Injector\Injector;

use function Codefy\Framework\Helpers\base_path;
use function Codefy\Framework\Helpers\config_path;
use function Codefy\Framework\Helpers\env;

return [
    /*
    |--------------------------------------------------------------------------
    | Aliases for the query bus.
    |--------------------------------------------------------------------------
    */
    'aliases' => [
        Injector::ARGUMENT_DEFINITIONS => [
            NativePdoDatabase::class => [
                'connection' => Devflow::$PHP->getDbConnection(),
                'configContainer' => Devflow::$PHP->configContainer,
            ],
            Collection::class => [
                'config' => [
                    'path' => config_path(),
                    'dotenv' => base_path(),
                    'environment' => env(key: 'APP_ENV', default: 'local'),
                ],
            ],
        ],
        Injector::STANDARD_ALIASES => [
            Container::class => InjectorContainer::class,
            ConfigContainer::class => Collection::class,
            Database::class => NativePdoDatabase::class,
            ContentQueryRepository::class => QueryBusContentRepository::class,
            ContentTypeQueryRepository::class => QueryBusContentTypeRepository::class,
            ProductQueryRepository::class => QueryBusProductRepository::class,
            SitesQueryRepository::class => QueryBusSitesRepository::class,
            UserQueryRepository::class => QueryBusUserRepository::class,
            ListenerProviderInterface::class => Devflow::$PHP->configContainer->string(key: 'app.event_listener'),
            EventDispatcherInterface::class => Devflow::$PHP->configContainer->string(key: 'app.event_dispatcher'),
        ],
    ],
];

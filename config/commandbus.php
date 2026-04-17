<?php

declare(strict_types=1);

use App\Application\Devflow;
use App\Domain\Content\Repository\ContentAggregateRepository;
use App\Domain\Content\Services\ContentProjection;
use App\Domain\ContentType\Repository\ContentTypeAggregateRepository;
use App\Domain\ContentType\Services\ContentTypeProjection;
use App\Domain\Product\Repository\ProductAggregateRepository;
use App\Domain\Product\Service\ProductProjection;
use App\Domain\Site\Repository\SiteCommandRepository;
use App\Domain\User\Repository\UserCommandRepository;
use Qubus\Expressive\Database;
use App\Infrastructure\Persistence\NativePdoDatabase;
use App\Infrastructure\Persistence\QueryBuilderTransactionalEventStore;
use App\Infrastructure\Persistence\Projection\DatabaseContentProjection;
use App\Infrastructure\Persistence\Projection\DatabaseContentTypeProjection;
use App\Infrastructure\Persistence\Projection\DatabaseProductProjection;
use App\Infrastructure\Persistence\Repository\EventSourcedContentRepository;
use App\Infrastructure\Persistence\Repository\EventSourcedContentTypeRepository;
use App\Infrastructure\Persistence\Repository\EventSourcedProductRepository;
use App\Infrastructure\Persistence\Repository\QueryBuilderSiteRepository;
use App\Infrastructure\Persistence\Repository\QueryBuilderUserRepository;
use Codefy\CommandBus\Container;
use Codefy\CommandBus\Containers\InjectorContainer;
use Codefy\Domain\EventSourcing\TransactionalEventStore;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Qubus\Config\Collection;
use Qubus\Config\ConfigContainer;
use Qubus\Injector\Injector;

use function Codefy\Framework\Helpers\base_path;
use function Codefy\Framework\Helpers\config_path;
use function Codefy\Framework\Helpers\env;

return [
    'container' => [
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
            TransactionalEventStore::class => QueryBuilderTransactionalEventStore::class,
            ContentProjection::class => DatabaseContentProjection::class,
            ContentTypeProjection::class => DatabaseContentTypeProjection::class,
            ProductProjection::class => DatabaseProductProjection::class,
            UserCommandRepository::class => QueryBuilderUserRepository::class,
            ContentAggregateRepository::class => EventSourcedContentRepository::class,
            ContentTypeAggregateRepository::class => EventSourcedContentTypeRepository::class,
            SiteCommandRepository::class => QueryBuilderSiteRepository::class,
            ProductAggregateRepository::class => EventSourcedProductRepository::class,
            ListenerProviderInterface::class => Devflow::$PHP->configContainer->string(key: 'app.event_listener'),
            EventDispatcherInterface::class => Devflow::$PHP->configContainer->string(key: 'app.event_dispatcher'),
        ]
    ]
];

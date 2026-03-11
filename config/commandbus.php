<?php

declare(strict_types=1);

use App\Application\Devflow;
use App\Domain\Content\Repository\ContentAggregateRepository;
use App\Domain\Content\Services\ContentProjection;
use App\Domain\ContentType\Repository\ContentTypeAggregateRepository;
use App\Domain\ContentType\Services\ContentTypeProjection;
use App\Domain\Product\Repository\ProductAggregateRepository;
use App\Domain\Product\Service\ProductProjection;
use App\Domain\Site\Repository\SiteAggregateRepository;
use App\Domain\Site\Services\SiteProjection;
use App\Domain\User\Repository\UserAggregateRepository;
use App\Domain\User\Services\UserProjection;
use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Persistence\NativePdoDatabase;
use App\Infrastructure\Persistence\QueryBuilderTransactionalEventStore;
use App\Infrastructure\Persistence\Projection\DatabaseContentProjection;
use App\Infrastructure\Persistence\Projection\DatabaseContentTypeProjection;
use App\Infrastructure\Persistence\Projection\DatabaseProductProjection;
use App\Infrastructure\Persistence\Projection\DatabaseSiteProjection;
use App\Infrastructure\Persistence\Projection\DatabaseUserProjection;
use App\Infrastructure\Persistence\Repository\EventSourcedContentRepository;
use App\Infrastructure\Persistence\Repository\EventSourcedContentTypeRepository;
use App\Infrastructure\Persistence\Repository\EventSourcedProductRepository;
use App\Infrastructure\Persistence\Repository\EventSourcedSiteRepository;
use App\Infrastructure\Persistence\Repository\EventSourcedUserRepository;
use Codefy\CommandBus\Container;
use Codefy\CommandBus\Containers\InjectorContainer;
use Codefy\Domain\EventSourcing\TransactionalEventStore;
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
                'pdo' => Devflow::$PHP->getDbConnection()->pdo,
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
            UserProjection::class => DatabaseUserProjection::class,
            ContentProjection::class => DatabaseContentProjection::class,
            ContentTypeProjection::class => DatabaseContentTypeProjection::class,
            SiteProjection::class => DatabaseSiteProjection::class,
            ProductProjection::class => DatabaseProductProjection::class,
            UserAggregateRepository::class => EventSourcedUserRepository::class,
            ContentAggregateRepository::class => EventSourcedContentRepository::class,
            ContentTypeAggregateRepository::class => EventSourcedContentTypeRepository::class,
            SiteAggregateRepository::class => EventSourcedSiteRepository::class,
            ProductAggregateRepository::class => EventSourcedProductRepository::class,
        ]
    ]
];

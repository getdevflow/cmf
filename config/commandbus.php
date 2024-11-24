<?php

declare(strict_types=1);

use App\Domain\Content\Repository\ContentRepository;
use App\Domain\Content\Services\ContentProjection;
use App\Domain\ContentType\Repository\ContentTypeRepository;
use App\Domain\ContentType\Services\ContentTypeProjection;
use App\Domain\Product\Repository\ProductRepository;
use App\Domain\Product\Service\ProductProjection;
use App\Domain\Site\Repository\SiteRepository;
use App\Domain\Site\Services\SiteProjection;
use App\Domain\User\Repository\UserRepository;
use App\Domain\User\Services\UserProjection;
use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Persistence\NativePdoDatabase;
use App\Infrastructure\Persistence\OrmTransactionalEventStore;
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
use Qubus\Injector\Injector;

return [
    'container' => [
        Injector::STANDARD_ALIASES => [
            Container::class => InjectorContainer::class,
            Database::class => NativePdoDatabase::class,
            TransactionalEventStore::class => OrmTransactionalEventStore::class,
            UserProjection::class => DatabaseUserProjection::class,
            ContentProjection::class => DatabaseContentProjection::class,
            ContentTypeProjection::class => DatabaseContentTypeProjection::class,
            SiteProjection::class => DatabaseSiteProjection::class,
            ProductProjection::class => DatabaseProductProjection::class,
            UserRepository::class => EventSourcedUserRepository::class,
            ContentRepository::class => EventSourcedContentRepository::class,
            ContentTypeRepository::class => EventSourcedContentTypeRepository::class,
            SiteRepository::class => EventSourcedSiteRepository::class,
            ProductRepository::class => EventSourcedProductRepository::class,
        ]
    ]
];

<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\Site\Repository\SiteRepository;
use App\Domain\Site\Services\SiteProjection;
use App\Infrastructure\Persistence\Trait\EventSourcedRepositoryAware;
use Codefy\Domain\EventSourcing\TransactionalEventStore;

final class EventSourcedSiteRepository implements SiteRepository
{
    use EventSourcedRepositoryAware;

    public function __construct(protected TransactionalEventStore $eventStore, protected SiteProjection $projection)
    {
    }
}

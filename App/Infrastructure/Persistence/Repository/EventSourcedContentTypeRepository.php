<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\ContentType\Repository\ContentTypeRepository;
use App\Domain\ContentType\Services\ContentTypeProjection;
use App\Infrastructure\Persistence\Trait\EventSourcedRepositoryAware;
use Codefy\Domain\EventSourcing\TransactionalEventStore;

class EventSourcedContentTypeRepository implements ContentTypeRepository
{
    use EventSourcedRepositoryAware;

    public function __construct(protected TransactionalEventStore $eventStore, protected ContentTypeProjection $projection)
    {
    }
}
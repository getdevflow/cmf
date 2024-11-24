<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repository;

use App\Domain\User\Repository\UserRepository;
use App\Domain\User\Services\UserProjection;
use App\Infrastructure\Persistence\Trait\EventSourcedRepositoryAware;
use Codefy\Domain\EventSourcing\TransactionalEventStore;

final class EventSourcedUserRepository implements UserRepository
{
    use EventSourcedRepositoryAware;

    public function __construct(protected TransactionalEventStore $eventStore, protected UserProjection $projection)
    {
    }
}
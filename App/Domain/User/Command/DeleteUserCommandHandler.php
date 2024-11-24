<?php

declare(strict_types=1);

namespace App\Domain\User\Command;

use App\Domain\User\Repository\UserRepository;
use App\Domain\User\User;
use Codefy\CommandBus\Command;
use Codefy\CommandBus\CommandHandler;
use Codefy\Domain\Aggregate\AggregateNotFoundException;
use Exception;

final readonly class DeleteUserCommandHandler implements CommandHandler
{
    public function __construct(public UserRepository $aggregateRepository)
    {
    }

    /**
     * @throws AggregateNotFoundException
     * @throws Exception
     */
    public function handle(DeleteUserCommand|Command $command): void
    {
        /** @var User $user */
        $user = $this->aggregateRepository->loadAggregateRoot(aggregateId: $command->id);

        $user->changeUserDeleted($command->id);

        $this->aggregateRepository->saveAggregateRoot(aggregate: $user);
    }
}

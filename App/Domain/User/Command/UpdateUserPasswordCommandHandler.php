<?php

declare(strict_types=1);

namespace App\Domain\User\Command;

use App\Domain\User\Repository\UserRepository;
use App\Domain\User\User;
use Codefy\CommandBus\Command;
use Codefy\CommandBus\CommandHandler;
use Codefy\Domain\Aggregate\AggregateNotFoundException;
use Codefy\Framework\Support\Password;
use Exception;
use Qubus\ValueObjects\StringLiteral\StringLiteral;

final readonly class UpdateUserPasswordCommandHandler implements CommandHandler
{
    public function __construct(public UserRepository $aggregateRepository)
    {
    }

    /**
     * @throws AggregateNotFoundException
     * @throws Exception
     */
    public function handle(UpdateUserPasswordCommand|Command $command): void
    {
        /** @var User $user */
        $user = $this->aggregateRepository->loadAggregateRoot(aggregateId: $command->id);

        $user->changeUserPassword(password: new StringLiteral(Password::hash($command->pass->toNative())));
        $user->changeUserToken($command->token);

        $this->aggregateRepository->saveAggregateRoot(aggregate: $user);
    }
}

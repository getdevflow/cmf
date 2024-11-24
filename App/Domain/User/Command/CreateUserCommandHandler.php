<?php

declare(strict_types=1);

namespace App\Domain\User\Command;

use App\Domain\User\Repository\UserRepository;
use App\Domain\User\User;
use Codefy\CommandBus\Command;
use Codefy\CommandBus\CommandHandler;
use Qubus\ValueObjects\Person\Name;
use Qubus\ValueObjects\StringLiteral\StringLiteral;

final readonly class CreateUserCommandHandler implements CommandHandler
{
    public function __construct(public UserRepository $aggregateRepository)
    {
    }

    /**
     * @param CreateUserCommand|Command $command
     */
    public function handle(CreateUserCommand|Command $command): void
    {
        $user = User::createUser(
            userId: $command->id,
            login: $command->login,
            name: new Name(
                firstName: $command->fname,
                middleName: $command->mname ?? new StringLiteral(''),
                lastName: $command->lname
            ),
            emailAddress: $command->email,
            token: $command->token,
            password: $command->pass,
            url: $command->url,
            timezone: $command->timezone,
            dateFormat: $command->dateFormat,
            timeFormat: $command->timeFormat,
            locale: $command->locale,
            registered: $command->registered,
            meta: $command->meta
        );

        $this->aggregateRepository->saveAggregateRoot(aggregate: $user);
    }
}

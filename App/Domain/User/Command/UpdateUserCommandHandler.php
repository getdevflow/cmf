<?php

declare(strict_types=1);

namespace App\Domain\User\Command;

use App\Domain\User\Repository\UserRepository;
use App\Domain\User\User;
use Codefy\CommandBus\Command;
use Codefy\CommandBus\CommandHandler;
use Codefy\Domain\Aggregate\AggregateNotFoundException;
use Exception;
use Qubus\ValueObjects\Person\Name;
use Qubus\ValueObjects\StringLiteral\StringLiteral;

use function Qubus\Support\Helpers\is_false__;

final readonly class UpdateUserCommandHandler implements CommandHandler
{
    public function __construct(public UserRepository $aggregateRepository)
    {
    }

    /**
     * @throws AggregateNotFoundException
     * @throws Exception
     */
    public function handle(UpdateUserCommand|Command $command): void
    {
        /** @var User $user */
        $user = $this->aggregateRepository->loadAggregateRoot(aggregateId: $command->id);

        $user->changeUserLogin($command->login);
        $user->changeUserEmailAddress($command->email);
        $user->changeUserName(
            new Name(
                firstName: $command->fname,
                middleName: $command->mname ?? new StringLiteral(''),
                lastName: $command->lname
            )
        );
        $user->changeUserUrl($command->url);
        $user->changeUserTimezone($command->timezone);
        $user->changeUserDateFormat($command->dateFormat);
        $user->changeUserTimeFormat($command->timeFormat);
        $user->changeUserLocale($command->locale);
        $user->changeUsermeta($command->meta);

        if (is_false__($command->pass->isEmpty())) {
            $user->changeUserPassword(password: $command->pass);
            $user->changeUserToken($command->token);
        }

        if ($user->hasRecordedEvents()) {
            $user->changeUserModified($command->modified);
        }

        $this->aggregateRepository->saveAggregateRoot(aggregate: $user);
    }
}

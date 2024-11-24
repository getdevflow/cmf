<?php

declare(strict_types=1);

namespace App\Domain\User\Event;

use App\Domain\User\ValueObject\UserId;
use App\Domain\User\ValueObject\UserToken;
use Codefy\Domain\Aggregate\AggregateId;
use Codefy\Domain\EventSourcing\AggregateChanged;
use Codefy\Domain\EventSourcing\DomainEvent;
use Codefy\Domain\Metadata;
use Qubus\Exception\Data\TypeException;

use function Qubus\Support\Helpers\is_null__;

class UserTokenWasChanged extends AggregateChanged
{
    private ?UserId $userId = null;

    private ?UserToken $userToken = null;

    public static function withData(
        UserId $userId,
        UserToken $userToken
    ): UserTokenWasChanged|DomainEvent|AggregateChanged {
        $event = self::occur(
            aggregateId: $userId,
            payload: [
                'user_token' => $userToken->toNative(),
            ],
            metadata: [
                Metadata::AGGREGATE_TYPE => 'user'
            ]
        );

        $event->userId = $userId;
        $event->userToken = $userToken;

        return $event;
    }

    /**
     * @throws TypeException
     */
    public function userId(): UserId|AggregateId
    {
        if (is_null__($this->userId)) {
            $this->userId = UserId::fromString(userId: $this->aggregateId()->__toString());
        }

        return $this->userId;
    }

    /**
     * @throws TypeException
     */
    public function userToken(): UserToken
    {
        if (is_null__($this->userToken)) {
            $this->userToken = UserToken::fromString(userToken: $this->payload()['user_token']);
        }

        return $this->userToken;
    }
}

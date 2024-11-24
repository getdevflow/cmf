<?php

declare(strict_types=1);

namespace App\Domain\User\Event;

use App\Domain\User\ValueObject\UserId;
use App\Domain\User\ValueObject\Username;
use Codefy\Domain\Aggregate\AggregateId;
use Codefy\Domain\EventSourcing\AggregateChanged;
use Codefy\Domain\EventSourcing\DomainEvent;
use Codefy\Domain\Metadata;
use Qubus\Exception\Data\TypeException;

use function Qubus\Support\Helpers\is_null__;

class UserLoginWasChanged extends AggregateChanged
{
    private ?UserId $userId = null;

    private ?Username $userLogin = null;

    public static function withData(
        UserId $userId,
        Username $userLogin
    ): UserLoginWasChanged|DomainEvent|AggregateChanged {
        $event = self::occur(
            aggregateId: $userId,
            payload: [
                'user_login' => $userLogin->toNative(),
            ],
            metadata: [
                Metadata::AGGREGATE_TYPE => 'user'
            ]
        );

        $event->userId = $userId;
        $event->userLogin = $userLogin;

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
    public function userLogin(): Username
    {
        if (is_null__($this->userLogin)) {
            $this->userLogin = Username::fromString($this->payload()['user_login']);
        }

        return $this->userLogin;
    }
}

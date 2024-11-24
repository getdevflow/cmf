<?php

declare(strict_types=1);

namespace App\Domain\User\Event;

use App\Domain\User\ValueObject\UserId;
use Codefy\Domain\Aggregate\AggregateId;
use Codefy\Domain\EventSourcing\AggregateChanged;
use Codefy\Domain\EventSourcing\DomainEvent;
use Codefy\Domain\Metadata;
use Qubus\Exception\Data\TypeException;
use Qubus\ValueObjects\StringLiteral\StringLiteral;
use SensitiveParameter;

use function Qubus\Support\Helpers\is_null__;

class UserPasswordWasChanged extends AggregateChanged
{
    private ?UserId $userId = null;

    private ?StringLiteral $password = null;

    public static function withData(
        UserId $userId,
        #[SensitiveParameter] StringLiteral $password
    ): UserPasswordWasChanged|DomainEvent|AggregateChanged {
        $event = self::occur(
            aggregateId: $userId,
            payload: [
                'user_pass' => $password->toNative(),
            ],
            metadata: [
                Metadata::AGGREGATE_TYPE => 'user'
            ]
        );

        $event->userId = $userId;
        $event->password = $password;

        return $event;
    }

    /**
     * @throws TypeException
     */
    public function userId(): UserId|AggregateId
    {
        if (is_null__(var: $this->userId)) {
            $this->userId = UserId::fromString(userId: $this->aggregateId()->__toString());
        }

        return $this->userId;
    }

    /**
     * @throws TypeException
     */
    public function userPass(): StringLiteral
    {
        if (is_null__(var: $this->password)) {
            $this->password = StringLiteral::fromNative($this->payload()['user_pass']);
        }

        return $this->password;
    }
}

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

use function Qubus\Support\Helpers\is_null__;

class UserTimeFormatWasChanged extends AggregateChanged
{
    private ?UserId $userId = null;

    private ?StringLiteral $userTimeFormat = null;

    public static function withData(
        UserId $userId,
        StringLiteral $userTimeFormat
    ): UserTimeFormatWasChanged|DomainEvent|AggregateChanged {
        $event = self::occur(
            aggregateId: $userId,
            payload: [
                'user_time_format' => $userTimeFormat->toNative(),
            ],
            metadata: [
                Metadata::AGGREGATE_TYPE => 'user'
            ]
        );

        $event->userId = $userId;
        $event->userTimeFormat = $userTimeFormat;

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
    public function userTimeFormat(): StringLiteral
    {
        if (is_null__($this->userTimeFormat)) {
            $this->userTimeFormat = StringLiteral::fromNative($this->payload()['user_time_format']);
        }

        return $this->userTimeFormat;
    }
}

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

class UserDateFormatWasChanged extends AggregateChanged
{
    private ?UserId $userId = null;

    private ?StringLiteral $userDateFormat = null;

    public static function withData(
        UserId $userId,
        StringLiteral $userDateFormat
    ): UserDateFormatWasChanged|DomainEvent|AggregateChanged {
        $event = self::occur(
            aggregateId: $userId,
            payload: [
                'user_date_format' => $userDateFormat->toNative(),
            ],
            metadata: [
                Metadata::AGGREGATE_TYPE => 'user'
            ]
        );

        $event->userId = $userId;
        $event->userDateFormat = $userDateFormat;

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
    public function userDateFormat(): StringLiteral
    {
        if (is_null__($this->userDateFormat)) {
            $this->userDateFormat = StringLiteral::fromNative($this->payload()['user_date_format']);
        }

        return $this->userDateFormat;
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\User\Event;

use App\Domain\User\ValueObject\UserId;
use App\Shared\ValueObject\ArrayLiteral;
use Codefy\Domain\Aggregate\AggregateId;
use Codefy\Domain\EventSourcing\AggregateChanged;
use Codefy\Domain\EventSourcing\DomainEvent;
use Codefy\Domain\Metadata;
use Qubus\Exception\Data\TypeException;

use function Qubus\Support\Helpers\is_null__;

class UserMetaWasChanged extends AggregateChanged
{
    private ?UserId $userId = null;

    private ?ArrayLiteral $meta = null;

    public static function withData(
        UserId $userId,
        ?ArrayLiteral $meta = null
    ): UserMetaWasChanged|DomainEvent|AggregateChanged {
        $event = self::occur(
            aggregateId: $userId,
            payload: [
                'meta' => $meta->toNative()
            ],
            metadata: [
                Metadata::AGGREGATE_TYPE => 'user',
            ]
        );

        $event->userId = $userId;
        $event->meta = $meta;

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

    public function usermeta(): ArrayLiteral
    {
        if (is_null__($this->meta)) {
            $this->meta = ArrayLiteral::fromNative($this->payload()['meta']);
        }

        return $this->meta;
    }
}

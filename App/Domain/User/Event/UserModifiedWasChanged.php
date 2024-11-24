<?php

declare(strict_types=1);

namespace App\Domain\User\Event;

use App\Domain\User\ValueObject\UserId;
use App\Shared\Services\DateTime;
use Codefy\Domain\Aggregate\AggregateId;
use Codefy\Domain\EventSourcing\AggregateChanged;
use Codefy\Domain\EventSourcing\DomainEvent;
use Codefy\Domain\Metadata;
use DateTimeInterface;
use Qubus\Exception\Data\TypeException;
use Qubus\Support\DateTime\QubusDateTimeImmutable;

use function Qubus\Support\Helpers\is_null__;

class UserModifiedWasChanged extends AggregateChanged
{
    private ?UserId $userId = null;

    private ?DateTimeInterface $userModified = null;

    public static function withData(
        UserId $userId,
        DateTimeInterface $userModified
    ): UserModifiedWasChanged|DomainEvent|AggregateChanged {
        $event = self::occur(
            aggregateId: $userId,
            payload: [
                'user_modified' => $userModified
            ],
            metadata: [
                Metadata::AGGREGATE_TYPE => 'user'
            ]
        );

        $event->userId = $userId;
        $event->userModified = $userModified;

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

    public function userModified(): DateTimeInterface
    {
        if (is_null__($this->userModified)) {
            $this->userModified = QubusDateTimeImmutable::createFromInterface(
                (new DateTime($this->payload()['user_modified']))->getDateTime()
            );
        }

        return $this->userModified;
    }
}

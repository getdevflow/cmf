<?php

declare(strict_types=1);

namespace App\Domain\User\Event;

use App\Domain\User\ValueObject\UserId;
use Codefy\Domain\Aggregate\AggregateId;
use Codefy\Domain\EventSourcing\AggregateChanged;
use Codefy\Domain\EventSourcing\DomainEvent;
use Codefy\Domain\Metadata;
use Qubus\Exception\Data\TypeException;
use Qubus\ValueObjects\Web\EmailAddress;

use function Qubus\Support\Helpers\is_null__;

class UserEmailAddressWasChanged extends AggregateChanged
{
    private ?UserId $userId = null;

    private ?EmailAddress $emailAddress = null;

    public static function withData(
        UserId $userId,
        EmailAddress $emailAddress
    ): UserEmailAddressWasChanged|DomainEvent|AggregateChanged {
        $event = self::occur(
            aggregateId: $userId,
            payload: [
                'user_email' => $emailAddress->toNative(),
            ],
            metadata: [
                Metadata::AGGREGATE_TYPE => 'user'
            ]
        );

        $event->userId = $userId;
        $event->emailAddress = $emailAddress;

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

    public function userEmail(): EmailAddress
    {
        if (is_null__($this->emailAddress)) {
            $this->emailAddress = EmailAddress::fromNative($this->payload()['user_email']);
        }

        return $this->emailAddress;
    }
}

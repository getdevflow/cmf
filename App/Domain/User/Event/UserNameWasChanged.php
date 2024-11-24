<?php

declare(strict_types=1);

namespace App\Domain\User\Event;

use App\Domain\User\ValueObject\UserId;
use Codefy\Domain\Aggregate\AggregateId;
use Codefy\Domain\EventSourcing\AggregateChanged;
use Codefy\Domain\EventSourcing\DomainEvent;
use Codefy\Domain\Metadata;
use Qubus\Exception\Data\TypeException;
use Qubus\ValueObjects\Person\Name;
use Qubus\ValueObjects\StringLiteral\StringLiteral;

use function Qubus\Support\Helpers\is_null__;

class UserNameWasChanged extends AggregateChanged
{
    private ?UserId $userId = null;

    private ?Name $name = null;

    public static function withData(
        UserId $userId,
        Name $name
    ): UserNameWasChanged|DomainEvent|AggregateChanged {
        $event = self::occur(
            aggregateId: $userId,
            payload: [
                'user_fname' => $name->getFirstName()->toNative(),
                'user_mname' => $name->getMiddleName()->toNative(),
                'user_lname' => $name->getLastName()->toNative(),
            ],
            metadata: [
                Metadata::AGGREGATE_TYPE => 'user'
            ]
        );

        $event->userId = $userId;
        $event->name = $name;

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
    public function name(): Name
    {
        if (is_null__($this->name)) {
            $this->name = Name::fromNative(
                $this->payload()['user_fname'],
                $this->payload()['user_mname'],
                $this->payload()['user_lname']
            );
        }

        return $this->name;
    }

    /**
     * @throws TypeException
     */
    public function userFname(): StringLiteral
    {
        if (is_null__($this->name)) {
            $this->name = Name::fromNative(
                $this->payload()['user_fname'],
                $this->payload()['user_mname'],
                $this->payload()['user_lname']
            );
        }

        return $this->name->getFirstName();
    }

    /**
     * @throws TypeException
     */
    public function userMname(): StringLiteral
    {
        if (is_null__($this->name)) {
            $this->name = Name::fromNative(
                $this->payload()['user_fname'],
                $this->payload()['user_mname'],
                $this->payload()['user_lname']
            );
        }

        return $this->name->getMiddleName();
    }

    /**
     * @throws TypeException
     */
    public function userLname(): StringLiteral
    {
        if (is_null__($this->name)) {
            $this->name = Name::fromNative(
                $this->payload()['user_fname'],
                $this->payload()['user_mname'],
                $this->payload()['user_lname']
            );
        }

        return $this->name->getLastName();
    }
}

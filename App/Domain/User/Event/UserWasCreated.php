<?php

declare(strict_types=1);

namespace App\Domain\User\Event;

use App\Domain\User\ValueObject\UserId;
use App\Domain\User\ValueObject\Username;
use App\Domain\User\ValueObject\UserToken;
use App\Shared\Services\DateTime;
use App\Shared\ValueObject\ArrayLiteral;
use Codefy\Domain\Aggregate\AggregateId;
use Codefy\Domain\EventSourcing\AggregateChanged;
use Codefy\Domain\EventSourcing\DomainEvent;
use Codefy\Domain\Metadata;
use Codefy\Framework\Support\Password;
use DateTimeInterface;
use Qubus\Exception\Data\TypeException;
use Qubus\Support\DateTime\QubusDateTimeImmutable;
use Qubus\ValueObjects\Person\Name;
use Qubus\ValueObjects\StringLiteral\StringLiteral;
use Qubus\ValueObjects\Web\EmailAddress;
use SensitiveParameter;

use function Qubus\Support\Helpers\is_null__;

class UserWasCreated extends AggregateChanged
{
    private ?UserId $id = null;

    private ?Username $login = null;

    private ?Name $name = null;

    private ?EmailAddress $emailAddress = null;

    private ?UserToken $token = null;

    private ?StringLiteral $password = null;

    private ?StringLiteral $url = null;

    private ?StringLiteral $timezone = null;

    private ?StringLiteral $dateFormat = null;

    private ?StringLiteral $timeFormat = null;

    private ?StringLiteral $locale = null;

    private ?DateTimeInterface $registered = null;

    private ?ArrayLiteral $meta = null;

    public static function withData(
        UserId $userId,
        Username $userLogin,
        Name $name,
        EmailAddress $emailAddress,
        UserToken $token,
        #[SensitiveParameter] StringLiteral $password,
        StringLiteral $url,
        StringLiteral $timezone,
        StringLiteral $dateFormat,
        StringLiteral $timeFormat,
        StringLiteral $locale,
        DateTimeInterface $registered,
        ?ArrayLiteral $meta = null,
    ): UserWasCreated|DomainEvent|AggregateChanged {
        $event = self::occur(
            aggregateId: $userId,
            payload: [
                'user_id' => $userId->toNative(),
                'user_login' => $userLogin->toNative(),
                'user_fname' => $name->getFirstName()->toNative(),
                'user_mname' => $name->getMiddleName()->toNative(),
                'user_lname' => $name->getLastName()->toNative(),
                'user_email' => $emailAddress->toNative(),
                'user_token' => $token->toNative(),
                'user_pass' => Password::hash($password->toNative()),
                'user_url' => $url->toNative(),
                'user_timezone' => $timezone->toNative(),
                'user_date_format' => $dateFormat->toNative(),
                'user_time_format' => $timeFormat->toNative(),
                'user_locale' => $locale->toNative(),
                'user_registered' => $registered,
                'meta' => $meta->toNative(),
            ],
            metadata: [
                Metadata::AGGREGATE_TYPE => 'user',
            ]
        );

        $event->id = $userId;
        $event->login = $userLogin;
        $event->name = $name;
        $event->emailAddress = $emailAddress;
        $event->token = $token;
        $event->password = $password;
        $event->url = $url;
        $event->timezone = $timezone;
        $event->dateFormat = $dateFormat;
        $event->timeFormat = $timeFormat;
        $event->locale = $locale;
        $event->registered = $registered;
        $event->meta = $meta;

        return $event;
    }

    /**
     * @throws TypeException
     */
    public function userId(): UserId|AggregateId
    {
        if (is_null__($this->id)) {
            $this->id = UserId::fromString(userId: $this->aggregateId()->__toString());
        }

        return $this->id;
    }

    /**
     * @throws TypeException
     */
    public function userLogin(): Username
    {
        if (is_null__($this->login)) {
            $this->login = Username::fromString($this->payload()['user_login']);
        }

        return $this->login;
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

    public function userEmail(): EmailAddress
    {
        if (is_null__($this->emailAddress)) {
            $this->emailAddress = EmailAddress::fromNative($this->payload()['user_email']);
        }

        return $this->emailAddress;
    }

    /**
     * @throws TypeException
     */
    public function userToken(): UserToken
    {
        if (is_null__($this->token)) {
            $this->token = UserToken::fromString($this->payload()['user_token']);
        }

        return $this->token;
    }

    /**
     * @throws TypeException
     */
    public function userPass(): StringLiteral
    {
        if (is_null__($this->password)) {
            $this->password = StringLiteral::fromNative($this->payload()['user_pass']);
        }

        return $this->password;
    }

    /**
     * @throws TypeException
     */
    public function userUrl(): StringLiteral
    {
        if (is_null__($this->url)) {
            $this->url = StringLiteral::fromNative($this->payload()['user_url']);
        }

        return $this->url;
    }

    /**
     * @throws TypeException
     */
    public function userTimezone(): StringLiteral
    {
        if (is_null__($this->timezone)) {
            $this->timezone = StringLiteral::fromNative($this->payload()['user_timezone']);
        }

        return $this->timezone;
    }

    /**
     * @throws TypeException
     */
    public function userDateFormat(): StringLiteral
    {
        if (is_null__($this->dateFormat)) {
            $this->dateFormat = StringLiteral::fromNative($this->payload()['user_date_format']);
        }

        return $this->dateFormat;
    }

    /**
     * @throws TypeException
     */
    public function userTimeFormat(): StringLiteral
    {
        if (is_null__($this->timeFormat)) {
            $this->timeFormat = StringLiteral::fromNative($this->payload()['user_time_format']);
        }

        return $this->timeFormat;
    }

    /**
     * @throws TypeException
     */
    public function userLocale(): StringLiteral
    {
        if (is_null__($this->locale)) {
            $this->locale = StringLiteral::fromNative($this->payload()['user_locale']);
        }

        return $this->locale;
    }

    public function userRegistered(): DateTimeInterface
    {
        if (is_null__($this->registered)) {
            $this->registered = QubusDateTimeImmutable::createFromInterface(
                (new DateTime($this->payload()['user_registered']))->getDateTime()
            );
        }

        return $this->registered;
    }

    /**
     * @throws TypeException
     */
    public function usermeta(): ArrayLiteral
    {
        if (is_null__($this->meta)) {
            $this->meta = ArrayLiteral::fromNative($this->payload()['meta']);
        }

        return $this->meta;
    }
}

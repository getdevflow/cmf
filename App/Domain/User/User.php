<?php

declare(strict_types=1);

namespace App\Domain\User;

use App\Domain\User\Event\UserDateFormatWasChanged;
use App\Domain\User\Event\UserEmailAddressWasChanged;
use App\Domain\User\Event\UserLocaleWasChanged;
use App\Domain\User\Event\UserLoginWasChanged;
use App\Domain\User\Event\UserModifiedWasChanged;
use App\Domain\User\Event\UserNameWasChanged;
use App\Domain\User\Event\UserPasswordWasChanged;
use App\Domain\User\Event\UserTimeFormatWasChanged;
use App\Domain\User\Event\UserTimezoneWasChanged;
use App\Domain\User\Event\UserTokenWasChanged;
use App\Domain\User\Event\UserUrlWasChanged;
use App\Domain\User\Event\UserMetaWasChanged;
use App\Domain\User\Event\UserWasCreated;
use App\Domain\User\Event\UserWasDeleted;
use App\Domain\User\ValueObject\UserId;
use App\Domain\User\ValueObject\Username;
use App\Domain\User\ValueObject\UserToken;
use App\Shared\ValueObject\ArrayLiteral;
use Codefy\Domain\Aggregate\AggregateId;
use Codefy\Domain\Aggregate\AggregateRoot;
use Codefy\Domain\Aggregate\EventSourcedAggregate;
use DateTimeInterface;
use Exception;
use Qubus\Exception\Data\TypeException;
use Qubus\ValueObjects\Person\Name;
use Qubus\ValueObjects\StringLiteral\StringLiteral;
use Qubus\ValueObjects\Web\EmailAddress;
use SensitiveParameter;

use function Qubus\Security\Helpers\t__;
use function Qubus\Support\Helpers\is_null__;

final class User extends EventSourcedAggregate implements AggregateRoot
{
    private ?UserId $userId = null;

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

    private ?DateTimeInterface $modified = null;

    private ?ArrayLiteral $meta = null;

    public static function createUser(
        UserId $userId,
        Username $login,
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
    ): User {
        $user = self::root(aggregateId: $userId);

        $user->recordApplyAndPublishThat(
            event: UserWasCreated::withData(
                userId: $userId,
                userLogin: $login,
                name: $name,
                emailAddress: $emailAddress,
                token: $token,
                password: $password,
                url: $url,
                timezone: $timezone,
                dateFormat: $dateFormat,
                timeFormat: $timeFormat,
                locale: $locale,
                registered: $registered,
                meta: $meta,
            )
        );

        return $user;
    }

    public static function fromNative(UserId $userId): User
    {
        return self::root(aggregateId: $userId);
    }

    public function userId(): UserId|AggregateId
    {
        return $this->userId;
    }

    public function login(): Username
    {
        return $this->login;
    }

    public function name(): Name
    {
        return $this->name;
    }

    public function emailAddress(): EmailAddress
    {
        return $this->emailAddress;
    }

    public function token(): UserToken
    {
        return $this->token;
    }

    public function password(): StringLiteral
    {
        return $this->password;
    }

    public function url(): StringLiteral
    {
        return $this->url;
    }

    public function timezone(): StringLiteral
    {
        return $this->timezone;
    }

    public function dateFormat(): StringLiteral
    {
        return $this->dateFormat;
    }

    public function timeFormat(): StringLiteral
    {
        return $this->timeFormat;
    }

    public function locale(): StringLiteral
    {
        return $this->locale;
    }

    public function registered(): DateTimeInterface
    {
        return $this->registered;
    }

    public function meta(): ArrayLiteral
    {
        return $this->meta;
    }

    /**
     * @throws Exception
     */
    public function changeUserLogin(Username $login): void
    {
        if ($login->isEmpty()) {
            throw new Exception(message: t__(msgid: 'Login/Username cannot be null.', domain: 'devflow'));
        }
        if ($login->equals($this->login)) {
            return;
        }
        $this->recordApplyAndPublishThat(
            event: UserLoginWasChanged::withData(userId: $this->userId, userLogin: $login)
        );
    }

    /**
     * @throws Exception
     */
    public function changeUserEmailAddress(EmailAddress $emailAddress): void
    {
        if ($emailAddress->isEmpty()) {
            throw new Exception(message: t__(msgid: 'Email address cannot be null.', domain: 'devflow'));
        }
        if ($emailAddress->equals($this->emailAddress)) {
            return;
        }
        $this->recordApplyAndPublishThat(
            event: UserEmailAddressWasChanged::withData(userId: $this->userId, emailAddress: $emailAddress)
        );
    }

    /**
     * @throws Exception
     */
    public function changeUserName(Name $name): void
    {
        if ($name->getFirstName()->isEmpty() && $name->getLastName()->isEmpty()) {
            throw new Exception(message: t__(msgid: 'Name cannot be null.', domain: 'devflow'));
        }
        if ($name->equals($this->name)) {
            return;
        }
        $this->recordApplyAndPublishThat(
            event: UserNameWasChanged::withData(userId: $this->userId, name: $name)
        );
    }

    /**
     * @throws Exception
     */
    public function changeUserToken(UserToken $token): void
    {
        if ($token->isEmpty()) {
            throw new Exception(message: t__(msgid: 'Token cannot be null.', domain: 'devflow'));
        }
        if ($token->equals($this->token)) {
            return;
        }
        $this->recordApplyAndPublishThat(
            event: UserTokenWasChanged::withData(userId: $this->userId, userToken: $token)
        );
    }

    /**
     * @throws Exception
     */
    public function changeUserPassword(StringLiteral $password): void
    {
        if ($password->isEmpty()) {
            throw new Exception(message: t__(msgid: 'Password cannot be null.', domain: 'devflow'));
        }
        if ($password->equals($this->password)) {
            return;
        }
        $this->recordApplyAndPublishThat(
            event: UserPasswordWasChanged::withData(userId: $this->userId, password: $password)
        );
    }

    /**
     * @throws Exception
     */
    public function changeUserUrl(StringLiteral $url): void
    {
        if ($url->isEmpty()) {
            return;
        }
        if ($url->equals($this->url)) {
            return;
        }
        $this->recordApplyAndPublishThat(UserUrlWasChanged::withData($this->userId, $url));
    }

    /**
     * @throws Exception
     */
    public function changeUserTimezone(StringLiteral $timezone): void
    {
        if ($timezone->isEmpty()) {
            throw new Exception(message: t__(msgid: 'Timezone cannot be null.', domain: 'devflow'));
        }
        if ($timezone->equals($this->timezone)) {
            return;
        }
        $this->recordApplyAndPublishThat(UserTimezoneWasChanged::withData($this->userId, $timezone));
    }

    /**
     * @throws Exception
     */
    public function changeUserDateFormat(StringLiteral $dateFormat): void
    {
        if ($dateFormat->isEmpty()) {
            throw new Exception(message: t__(msgid: 'Date format cannot be null.', domain: 'devflow'));
        }
        if ($dateFormat->equals($this->dateFormat)) {
            return;
        }
        $this->recordApplyAndPublishThat(UserDateFormatWasChanged::withData($this->userId, $dateFormat));
    }

    /**
     * @throws Exception
     */
    public function changeUserTimeFormat(StringLiteral $timeFormat): void
    {
        if ($timeFormat->isEmpty()) {
            throw new Exception(message: t__(msgid: 'Time format cannot be null.', domain: 'devflow'));
        }
        if ($timeFormat->equals($this->timeFormat)) {
            return;
        }
        $this->recordApplyAndPublishThat(UserTimeFormatWasChanged::withData($this->userId, $timeFormat));
    }

    /**
     * @throws Exception
     */
    public function changeUserLocale(StringLiteral $locale): void
    {
        if ($locale->isEmpty()) {
            throw new Exception(message: t__(msgid: 'Locale cannot be null.', domain: 'devflow'));
        }
        if ($locale->equals($this->locale)) {
            return;
        }
        $this->recordApplyAndPublishThat(UserLocaleWasChanged::withData($this->userId, $locale));
    }

    /**
     * @throws Exception
     */
    public function changeUserModified(DateTimeInterface $modified): void
    {
        if (
                !is_null__($this->modified) &&
                ($this->modified->getTimestamp() === $modified->getTimestamp())
        ) {
            return;
        }
        $this->recordApplyAndPublishThat(UserModifiedWasChanged::withData($this->userId, $modified));
    }

    /**
     * @param UserId $userId
     * @return void
     * @throws Exception
     */
    public function changeUserDeleted(UserId $userId): void
    {
        if ($userId->isEmpty()) {
            throw new Exception(message: t__(msgid: 'User id cannot be null.', domain: 'devflow'));
        }
        if ($this->userId->toNative() !== $userId->toNative()) {
            return;
        }
        $this->recordApplyAndPublishThat(UserWasDeleted::withData($this->userId));
    }

    /**
     * @throws Exception
     */
    public function changeUsermeta(ArrayLiteral $meta): void
    {
        if ($meta->isEmpty()) {
            throw new Exception(message: t__(msgid: 'User meta cannot be empty.', domain: 'devflow'));
        }

        if ($meta->equals($this->meta)) {
            return;
        }

        $this->recordApplyAndPublishThat(UserMetaWasChanged::withData($this->userId, $meta));
    }

    /**
     * @throws TypeException
     */
    public function whenUserWasCreated(UserWasCreated $event): void
    {
        $this->userId = $event->userId();
        $this->login = $event->userLogin();
        $this->name = $event->name();
        $this->emailAddress = $event->userEmail();
        $this->token = $event->userToken();
        $this->password = $event->userPass();
        $this->url = $event->userUrl();
        $this->timezone = $event->userTimezone();
        $this->dateFormat = $event->userDateFormat();
        $this->timeFormat = $event->userTimeFormat();
        $this->locale = $event->userLocale();
        $this->meta = $event->usermeta();
        $this->registered = $event->userRegistered();
    }

    /**
     * @throws TypeException
     */
    public function whenUserLoginWasChanged(UserLoginWasChanged $event): void
    {
        $this->userId = $event->userId();
        $this->login = $event->userLogin();
    }

    /**
     * @throws TypeException
     */
    public function whenUserNameWasChanged(UserNameWasChanged $event): void
    {
        $this->userId = $event->userId();
        $this->name = $event->name();
    }

    /**
     * @throws TypeException
     */
    public function whenUserEmailAddressWasChanged(UserEmailAddressWasChanged $event): void
    {
        $this->userId = $event->userId();
        $this->emailAddress = $event->userEmail();
    }

    /**
     * @throws TypeException
     */
    public function whenUserTokenWasChanged(UserTokenWasChanged $event): void
    {
        $this->userId = $event->userId();
        $this->token = $event->userToken();
    }

    /**
     * @throws TypeException
     */
    public function whenUserPasswordWasChanged(UserPasswordWasChanged $event): void
    {
        $this->userId = $event->userId();
        $this->password = $event->userPass();
    }

    /**
     * @throws TypeException
     */
    public function whenUserUrlWasChanged(UserUrlWasChanged $event): void
    {
        $this->userId = $event->userId();
        $this->url = $event->userUrl();
    }

    /**
     * @throws TypeException
     */
    public function whenUserTimezoneWasChanged(UserTimezoneWasChanged $event): void
    {
        $this->userId = $event->userId();
        $this->timezone = $event->userTimezone();
    }

    /**
     * @throws TypeException
     */
    public function whenUserDateFormatWasChanged(UserDateFormatWasChanged $event): void
    {
        $this->userId = $event->userId();
        $this->dateFormat = $event->userDateFormat();
    }

    /**
     * @throws TypeException
     */
    public function whenUserTimeFormatWasChanged(UserTimeFormatWasChanged $event): void
    {
        $this->userId = $event->userId();
        $this->timeFormat = $event->userTimeFormat();
    }

    /**
     * @throws TypeException
     */
    public function whenUserLocaleWasChanged(UserLocaleWasChanged $event): void
    {
        $this->userId = $event->userId();
        $this->locale = $event->userLocale();
    }

    /**
     * @throws TypeException
     */
    public function whenUserModifiedWasChanged(UserModifiedWasChanged $event): void
    {
        $this->userId = $event->userId();
        $this->modified = $event->userModified();
    }

    /**
     * @throws TypeException
     */
    public function whenUserMetaWasChanged(UserMetaWasChanged $event): void
    {
        $this->userId = $event->userId();
        $this->meta = $event->usermeta();
    }

    /**
     * @throws TypeException
     */
    public function whenUserWasDeleted(UserWasDeleted $event): void
    {
        $this->userId = $event->userId();
    }
}

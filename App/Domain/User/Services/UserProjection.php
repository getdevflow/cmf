<?php

declare(strict_types=1);

namespace App\Domain\User\Services;

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
use Codefy\Domain\EventSourcing\Projection;

interface UserProjection extends Projection
{
    /**
     * Projects when a user was created.
     *
     * @param UserWasCreated $event
     * @return void
     */
    public function projectWhenUserWasCreated(UserWasCreated $event): void;

    /**
     * Projects when login was changed.
     *
     * @param UserLoginWasChanged $event
     * @return void
     */
    public function projectWhenUserLoginWasChanged(UserLoginWasChanged $event): void;

    /**
     * Projects when name was changed.
     *
     * @param UserNameWasChanged $event
     * @return void
     */
    public function projectWhenUserNameWasChanged(UserNameWasChanged $event): void;

    /**
     * Projects when email address was changed.
     *
     * @param UserEmailAddressWasChanged $event
     * @return void
     */
    public function projectWhenUserEmailAddressWasChanged(UserEmailAddressWasChanged $event): void;

    /**
     * Projects when token was changed.
     *
     * @param UserTokenWasChanged $event
     * @return void
     */
    public function projectWhenUserTokenWasChanged(UserTokenWasChanged $event): void;

    /**
     * Projects when password was changed.
     *
     * @param UserPasswordWasChanged $event
     * @return void
     */
    public function projectWhenUserPasswordWasChanged(UserPasswordWasChanged $event): void;

    /**
     * Projects when url was changed.
     *
     * @param UserUrlWasChanged $event
     * @return void
     */
    public function projectWhenUserUrlWasChanged(UserUrlWasChanged $event): void;

    /**
     * Projects when timezone was changed.
     *
     * @param UserTimezoneWasChanged $event
     * @return void
     */
    public function projectWhenUserTimezoneWasChanged(UserTimezoneWasChanged $event): void;

    /**
     * Projects when date format was changed.
     *
     * @param UserDateFormatWasChanged $event
     * @return void
     */
    public function projectWhenUserDateFormatWasChanged(UserDateFormatWasChanged $event): void;

    /**
     * Projects when time format was changed.
     *
     * @param UserTimeFormatWasChanged $event
     * @return void
     */
    public function projectWhenUserTimeFormatWasChanged(UserTimeFormatWasChanged $event): void;

    /**
     * Projects when locale was changed.
     *
     * @param UserLocaleWasChanged $event
     * @return void
     */
    public function projectWhenUserLocaleWasChanged(UserLocaleWasChanged $event): void;

    /**
     * Projects when modified date was changed.
     *
     * @param UserModifiedWasChanged $event
     * @return void
     */
    public function projectWhenUserModifiedWasChanged(UserModifiedWasChanged $event): void;

    /**
     * @param UserMetaWasChanged $event
     * @return void
     */
    public function projectWhenUserMetaWasChanged(UserMetaWasChanged $event): void;

    /**
     * Projects when user is deleted.
     *
     * @param UserWasDeleted $event
     * @return void
     */
    public function projectWhenUserWasDeleted(UserWasDeleted $event): void;
}

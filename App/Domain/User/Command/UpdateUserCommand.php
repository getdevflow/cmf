<?php

declare(strict_types=1);

namespace App\Domain\User\Command;

use App\Domain\User\ValueObject\UserId;
use App\Domain\User\ValueObject\Username;
use App\Domain\User\ValueObject\UserToken;
use App\Shared\ValueObject\ArrayLiteral;
use Codefy\CommandBus\PropertyCommand;
use DateTimeInterface;
use Qubus\ValueObjects\StringLiteral\StringLiteral;
use Qubus\ValueObjects\Web\EmailAddress;

final class UpdateUserCommand extends PropertyCommand
{
    public ?UserId $id = null;

    public ?StringLiteral $fname = null;

    public ?StringLiteral $mname = null;

    public ?StringLiteral $lname = null;

    public ?EmailAddress $email = null;

    public ?Username $login = null;

    public ?UserToken $token = null;

    public ?StringLiteral $pass = null;

    public ?StringLiteral $url = null;

    public ?StringLiteral $timezone = null;

    public ?StringLiteral $dateFormat = null;

    public ?StringLiteral $timeFormat = null;

    public ?StringLiteral $locale = null;

    public ?DateTimeInterface $modified = null;

    public ?StringLiteral $activationKey = null;

    public ?ArrayLiteral $meta = null;
}

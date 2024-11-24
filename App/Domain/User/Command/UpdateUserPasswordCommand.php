<?php

declare(strict_types=1);

namespace App\Domain\User\Command;

use App\Domain\User\ValueObject\UserId;
use App\Domain\User\ValueObject\UserToken;
use Codefy\CommandBus\PropertyCommand;
use Qubus\ValueObjects\StringLiteral\StringLiteral;

final class UpdateUserPasswordCommand extends PropertyCommand
{
    public ?UserId $id = null;

    public ?UserToken $token = null;

    public ?StringLiteral $pass = null;
}

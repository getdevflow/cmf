<?php

declare(strict_types=1);

namespace App\Domain\User\Command;

use App\Domain\User\ValueObject\UserId;
use Codefy\CommandBus\PropertyCommand;

final class DeleteUserCommand extends PropertyCommand
{
    public ?UserId $id = null;
}

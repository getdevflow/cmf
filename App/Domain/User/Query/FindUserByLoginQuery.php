<?php

declare(strict_types=1);

namespace App\Domain\User\Query;

use App\Domain\User\ValueObject\Username;
use Codefy\CommandBus\PropertyCommand;
use Codefy\QueryBus\Query;

final class FindUserByLoginQuery extends PropertyCommand implements Query
{
    public ?Username $userLogin = null;
}

<?php

declare(strict_types=1);

namespace App\Domain\User\Query;

use App\Domain\User\ValueObject\UserId;
use Codefy\CommandBus\PropertyCommand;
use Codefy\QueryBus\Query;

final class FindUserByIdQuery extends PropertyCommand implements Query
{
    public ?UserId $userId = null;
}

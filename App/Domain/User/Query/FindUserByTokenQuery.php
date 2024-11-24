<?php

declare(strict_types=1);

namespace App\Domain\User\Query;

use App\Domain\User\ValueObject\UserToken;
use Codefy\CommandBus\PropertyCommand;
use Codefy\QueryBus\Query;

final class FindUserByTokenQuery extends PropertyCommand implements Query
{
    public ?UserToken $userToken = null;
}

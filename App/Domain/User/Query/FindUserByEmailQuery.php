<?php

declare(strict_types=1);

namespace App\Domain\User\Query;

use Codefy\CommandBus\PropertyCommand;
use Codefy\QueryBus\Query;
use Qubus\ValueObjects\Web\EmailAddress;

final class FindUserByEmailQuery extends PropertyCommand implements Query
{
    public ?EmailAddress $userEmail = null;
}

<?php

declare(strict_types=1);

namespace App\Domain\Site\Query;

use App\Domain\User\ValueObject\UserId;
use Codefy\CommandBus\PropertyCommand;
use Codefy\QueryBus\Query;

class FindSitesByOwnerQuery extends PropertyCommand implements Query
{
    public ?UserId $siteOwner = null;
}

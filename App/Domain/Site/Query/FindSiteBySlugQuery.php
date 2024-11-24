<?php

declare(strict_types=1);

namespace App\Domain\Site\Query;

use Codefy\CommandBus\PropertyCommand;
use Codefy\QueryBus\Query;
use Qubus\ValueObjects\StringLiteral\StringLiteral;

class FindSiteBySlugQuery extends PropertyCommand implements Query
{
    public ?StringLiteral $siteSlug = null;
}

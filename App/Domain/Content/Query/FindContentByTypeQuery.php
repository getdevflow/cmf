<?php

declare(strict_types=1);

namespace App\Domain\Content\Query;

use Codefy\CommandBus\PropertyCommand;
use Codefy\QueryBus\Query;
use Qubus\ValueObjects\StringLiteral\StringLiteral;

final class FindContentByTypeQuery extends PropertyCommand implements Query
{
    public ?StringLiteral $contentType = null;
}

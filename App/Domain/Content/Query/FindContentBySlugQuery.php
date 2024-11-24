<?php

declare(strict_types=1);

namespace App\Domain\Content\Query;

use Codefy\CommandBus\PropertyCommand;
use Codefy\QueryBus\Query;
use Qubus\ValueObjects\StringLiteral\StringLiteral;

final class FindContentBySlugQuery extends PropertyCommand implements Query
{
    public ?StringLiteral $contentSlug = null;
}

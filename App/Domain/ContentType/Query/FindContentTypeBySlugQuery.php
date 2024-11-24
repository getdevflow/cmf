<?php

declare(strict_types=1);

namespace App\Domain\ContentType\Query;

use Codefy\CommandBus\PropertyCommand;
use Codefy\QueryBus\Query;
use Qubus\ValueObjects\StringLiteral\StringLiteral;

class FindContentTypeBySlugQuery extends PropertyCommand implements Query
{
    public ?StringLiteral $contentTypeSlug = null;
}

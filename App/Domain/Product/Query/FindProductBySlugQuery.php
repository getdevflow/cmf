<?php

declare(strict_types=1);

namespace App\Domain\Product\Query;

use Codefy\CommandBus\PropertyCommand;
use Codefy\QueryBus\Query;
use Qubus\ValueObjects\StringLiteral\StringLiteral;

final class FindProductBySlugQuery extends PropertyCommand implements Query
{
    public ?StringLiteral $productSlug = null;
}

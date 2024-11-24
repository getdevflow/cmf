<?php

declare(strict_types=1);

namespace App\Domain\Product\Query;

use Codefy\CommandBus\PropertyCommand;
use Codefy\QueryBus\Query;
use Qubus\ValueObjects\StringLiteral\StringLiteral;

final class FindProductBySkuQuery extends PropertyCommand implements Query
{
    public ?StringLiteral $productSku = null;
}

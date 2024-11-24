<?php

declare(strict_types=1);

namespace App\Domain\Product\Query;

use App\Domain\Product\ValueObject\ProductId;
use Codefy\CommandBus\PropertyCommand;
use Codefy\QueryBus\Query;

final class FindProductByIdQuery extends PropertyCommand implements Query
{
    public ?ProductId $productId = null;
}

<?php

declare(strict_types=1);

namespace App\Domain\Product\Query;

use Codefy\CommandBus\PropertyCommand;
use Codefy\QueryBus\Query;

final class FindProductsQuery extends PropertyCommand implements Query
{
    public ?string $productSku = null;

    public ?int $limit = 0;

    public ?int $offset = null;

    public string $status = 'all';
}

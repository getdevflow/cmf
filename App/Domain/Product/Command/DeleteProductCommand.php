<?php

declare(strict_types=1);

namespace App\Domain\Product\Command;

use App\Domain\Product\ValueObject\ProductId;
use Codefy\CommandBus\PropertyCommand;

class DeleteProductCommand extends PropertyCommand
{
    public ?ProductId $productId = null;
}

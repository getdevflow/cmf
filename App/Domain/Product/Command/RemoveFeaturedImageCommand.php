<?php

declare(strict_types=1);

namespace App\Domain\Product\Command;

use App\Domain\Product\ValueObject\ProductId;
use Codefy\CommandBus\PropertyCommand;
use Qubus\ValueObjects\StringLiteral\StringLiteral;

class RemoveFeaturedImageCommand extends PropertyCommand
{
    public ?ProductId $productId = null;

    public ?StringLiteral $productFeaturedImage = null;
}

<?php

declare(strict_types=1);

namespace App\Domain\Product\ValueObject;

use App\Domain\Product\Product;
use Codefy\Domain\Aggregate\AggregateId;
use Qubus\Exception\Data\TypeException;
use Qubus\ValueObjects\Identity\Ulid;

class ProductId extends Ulid implements AggregateId
{
    public function aggregateClassName(): string
    {
        return Product::className();
    }

    /**
     * @throws TypeException
     */
    public static function fromString(string $productId): ProductId
    {
        return new self(value: $productId);
    }
}

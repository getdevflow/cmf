<?php

declare(strict_types=1);

namespace App\Domain\Product\Event;

use App\Domain\Product\ValueObject\ProductId;
use Codefy\Domain\Aggregate\AggregateId;
use Codefy\Domain\EventSourcing\AggregateChanged;
use Codefy\Domain\EventSourcing\DomainEvent;
use Codefy\Domain\Metadata;
use Qubus\Exception\Data\TypeException;
use Qubus\ValueObjects\StringLiteral\StringLiteral;

use function Qubus\Support\Helpers\is_null__;

final class ProductSkuWasChanged extends AggregateChanged
{
    private ?ProductId $productId = null;

    private ?StringLiteral $productSku = null;

    public static function withData(
        ProductId $productId,
        StringLiteral $productSku,
    ): ProductSkuWasChanged|DomainEvent|AggregateChanged {
        $event = self::occur(
            aggregateId: $productId,
            payload: [
                'product_sku' => $productSku->toNative(),
            ],
            metadata: [
                Metadata::AGGREGATE_TYPE => 'product',
            ],
        );

        $event->productId = $productId;
        $event->productSku = $productSku;

        return $event;
    }

    /**
     * @throws TypeException
     */
    public function productId(): ProductId|AggregateId
    {
        if (is_null__($this->productId)) {
            $this->productId = ProductId::fromString(productId: $this->aggregateId()->__toString());
        }

        return $this->productId;
    }

    /**
     * @throws TypeException
     */
    public function productSku(): StringLiteral
    {
        if (is_null__($this->productSku)) {
            $this->productSku = StringLiteral::fromNative($this->payload()['product_sku']);
        }

        return $this->productSku;
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Product\Event;

use App\Domain\Product\ValueObject\ProductId;
use Codefy\Domain\Aggregate\AggregateId;
use Codefy\Domain\EventSourcing\AggregateChanged;
use Codefy\Domain\EventSourcing\DomainEvent;
use Codefy\Domain\Metadata;
use Qubus\Exception\Data\TypeException;
use Qubus\ValueObjects\Money\Money;

use function Qubus\Support\Helpers\is_null__;

final class ProductPriceWasChanged extends AggregateChanged
{
    private ?ProductId $productId = null;

    private ?Money $productPrice = null;

    /**
     * @throws TypeException
     */
    public static function withData(
        ProductId $productId,
        Money $productPrice,
    ): ProductPriceWasChanged|DomainEvent|AggregateChanged {
        $event = self::occur(
            aggregateId: $productId,
            payload: [
                'product_price' => $productPrice->getAmount()->toNative(),
                'product_currency' => $productPrice->getCurrency()->getCode()->toNative(),
            ],
            metadata: [
                Metadata::AGGREGATE_TYPE => 'product',
            ],
        );

        $event->productId = $productId;
        $event->productPrice = $productPrice;

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
    public function productPrice(): Money
    {
        if (is_null__($this->productPrice)) {
            $this->productPrice = Money::fromNative(
                $this->payload()['product_price'],
                $this->payload()['product_currency']
            );
        }

        return $this->productPrice;
    }
}

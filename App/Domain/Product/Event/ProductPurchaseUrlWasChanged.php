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

final class ProductPurchaseUrlWasChanged extends AggregateChanged
{
    private ?ProductId $productId = null;

    private ?StringLiteral $productPurchaseUrl = null;

    public static function withData(
        ProductId $productId,
        ?StringLiteral $productPurchaseUrl = null,
    ): ProductPurchaseUrlWasChanged|DomainEvent|AggregateChanged {
        $event = self::occur(
            aggregateId: $productId,
            payload: [
                'product_purchase_url' => $productPurchaseUrl?->toNative(),
            ],
            metadata: [
                Metadata::AGGREGATE_TYPE => 'product',
            ],
        );

        $event->productId = $productId;
        $event->productPurchaseUrl = $productPurchaseUrl;

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
    public function productPurchaseUrl(): StringLiteral
    {
        if (is_null__($this->productPurchaseUrl)) {
            $this->productPurchaseUrl = StringLiteral::fromNative($this->payload()['product_purchase_url']);
        }

        return $this->productPurchaseUrl;
    }
}

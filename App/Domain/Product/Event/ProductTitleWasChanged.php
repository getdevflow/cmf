<?php

declare(strict_types=1);

namespace App\Domain\Product\Event;

use App\Domain\Product\ValueObject\ProductId;
use Codefy\Domain\EventSourcing\AggregateChanged;
use Codefy\Domain\EventSourcing\DomainEvent;
use Codefy\Domain\Metadata;
use Qubus\Exception\Data\TypeException;
use Qubus\ValueObjects\StringLiteral\StringLiteral;

use function Qubus\Support\Helpers\is_null__;

final class ProductTitleWasChanged extends AggregateChanged
{
    private ?ProductId $productId = null;

    private ?StringLiteral $productTitle = null;

    public static function withData(
        ProductId $productId,
        StringLiteral $productTitle,
    ): ProductTitleWasChanged|DomainEvent|AggregateChanged {
        $event = self::occur(
            aggregateId: $productId,
            payload: [
                'product_title' => $productTitle->toNative(),
            ],
            metadata: [
                Metadata::AGGREGATE_TYPE => 'product',
            ]
        );

        $event->productId = $productId;
        $event->productTitle = $productTitle;

        return $event;
    }

    /**
     * @throws TypeException
     */
    public function productId(): ProductId
    {
        if (is_null__($this->productId)) {
            $this->productId = ProductId::fromString($this->aggregateId()->__toString());
        }

        return $this->productId;
    }

    /**
     * @throws TypeException
     */
    public function productTitle(): StringLiteral
    {
        if (is_null__($this->productTitle)) {
            $this->productTitle = StringLiteral::fromNative($this->payload()['product_title']);
        }

        return $this->productTitle;
    }
}

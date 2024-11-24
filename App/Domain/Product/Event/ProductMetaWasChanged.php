<?php

declare(strict_types=1);

namespace App\Domain\Product\Event;

use App\Domain\Product\ValueObject\ProductId;
use App\Shared\ValueObject\ArrayLiteral;
use Codefy\Domain\Aggregate\AggregateId;
use Codefy\Domain\EventSourcing\AggregateChanged;
use Codefy\Domain\EventSourcing\DomainEvent;
use Codefy\Domain\Metadata;
use Qubus\Exception\Data\TypeException;

use function Qubus\Support\Helpers\is_null__;

class ProductMetaWasChanged extends AggregateChanged
{
    private ?ProductId $productId = null;

    private ?ArrayLiteral $meta = null;

    public static function withData(
        ProductId $productId,
        ?ArrayLiteral $meta = null
    ): ProductMetaWasChanged|DomainEvent|AggregateChanged {
        $event = self::occur(
            aggregateId: $productId,
            payload: [
                'meta' => $meta->toNative()
            ],
            metadata: [
                Metadata::AGGREGATE_TYPE => 'product',
            ]
        );

        $event->productId = $productId;
        $event->meta = $meta;

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
    public function productMeta(): ArrayLiteral
    {
        if (is_null__($this->meta)) {
            $this->meta = ArrayLiteral::fromNative($this->payload()['meta']);
        }

        return $this->meta;
    }
}

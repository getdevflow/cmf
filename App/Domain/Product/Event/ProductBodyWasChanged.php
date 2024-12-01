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

final class ProductBodyWasChanged extends AggregateChanged
{
    private ?ProductId $productId = null;

    private ?StringLiteral $productBody = null;

    public static function withData(
        ProductId $productId,
        StringLiteral $productBody,
    ): ProductBodyWasChanged|DomainEvent|AggregateChanged {
        $event = self::occur(
            aggregateId: $productId,
            payload: [
                'product_body' => $productBody->toNative(),
            ],
            metadata: [
                Metadata::AGGREGATE_TYPE => 'product',
            ]
        );

        $event->productId = $productId;
        $event->productBody = $productBody;

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
    public function productBody(): StringLiteral
    {
        if (is_null__($this->productBody)) {
            $this->productBody = StringLiteral::fromNative($this->payload()['product_body']);
        }

        return $this->productBody;
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Product\Event;

use App\Domain\Product\ValueObject\ProductId;
use Codefy\Domain\EventSourcing\AggregateChanged;
use Codefy\Domain\EventSourcing\DomainEvent;
use Codefy\Domain\Metadata;
use Qubus\Exception\Data\TypeException;
use Qubus\ValueObjects\Number\IntegerNumber;

use function Qubus\Support\Helpers\is_null__;

final class ProductShowInSearchWasChanged extends AggregateChanged
{
    private ?ProductId $productId = null;

    private ?IntegerNumber $productShowInSearch = null;

    public static function withData(
        ProductId $productId,
        IntegerNumber $productShowInSearch,
    ): ProductShowInSearchWasChanged|DomainEvent|AggregateChanged {
        $event = self::occur(
            aggregateId: $productId,
            payload: [
                'product_show_in_search' => $productShowInSearch->toNative(),
            ],
            metadata: [
                Metadata::AGGREGATE_TYPE => 'product',
            ]
        );

        $event->productId = $productId;
        $event->productShowInSearch = $productShowInSearch;

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

    public function productShowInSearch(): IntegerNumber
    {
        if (is_null__($this->productShowInSearch)) {
            $this->productShowInSearch = IntegerNumber::fromNative($this->payload()['product_show_in_search']);
        }

        return $this->productShowInSearch;
    }
}

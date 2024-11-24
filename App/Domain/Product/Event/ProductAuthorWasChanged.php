<?php

declare(strict_types=1);

namespace App\Domain\Product\Event;

use App\Domain\Product\ValueObject\ProductId;
use App\Domain\User\ValueObject\UserId;
use Codefy\Domain\EventSourcing\AggregateChanged;
use Codefy\Domain\EventSourcing\DomainEvent;
use Codefy\Domain\Metadata;
use Qubus\Exception\Data\TypeException;

use function Qubus\Support\Helpers\is_null__;

final class ProductAuthorWasChanged extends AggregateChanged
{
    private ?ProductId $productId = null;

    private ?UserId $productAuthor = null;

    public static function withData(
        ProductId $productId,
        UserId $productAuthor,
    ): ProductAuthorWasChanged|DomainEvent|AggregateChanged {
        $event = self::occur(
            aggregateId: $productId,
            payload: [
                'product_author' => $productAuthor->toNative(),
            ],
            metadata: [
                Metadata::AGGREGATE_TYPE => 'product',
            ]
        );

        $event->productId = $productId;
        $event->productAuthor = $productAuthor;

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
    public function productAuthor(): UserId
    {
        if (is_null__($this->productAuthor)) {
            $this->productAuthor = UserId::fromString($this->payload()['product_author']);
        }

        return $this->productAuthor;
    }
}

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

final class ProductFeaturedImageWasChanged extends AggregateChanged
{
    private ?ProductId $productId = null;

    private ?StringLiteral $productFeaturedImage = null;

    public static function withData(
        ProductId $productId,
        StringLiteral $productFeaturedImage,
    ): ProductFeaturedImageWasChanged|DomainEvent|AggregateChanged {
        $event = self::occur(
            aggregateId: $productId,
            payload: [
                'product_featured_image' => $productFeaturedImage->toNative(),
            ],
            metadata: [
                Metadata::AGGREGATE_TYPE => 'product',
            ]
        );

        $event->productId = $productId;
        $event->productFeaturedImage = $productFeaturedImage;

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
    public function productFeaturedImage(): StringLiteral
    {
        if (is_null__($this->productFeaturedImage)) {
            $this->productFeaturedImage = StringLiteral::fromNative($this->payload()['product_featured_image']);
        }

        return $this->productFeaturedImage;
    }
}

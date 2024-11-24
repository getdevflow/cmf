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

final class ProductShowInMenuWasChanged extends AggregateChanged
{
    private ?ProductId $productId = null;

    private ?IntegerNumber $productShowInMenu = null;

    public static function withData(
        ProductId $productId,
        IntegerNumber $productShowInMenu,
    ): ProductShowInMenuWasChanged|DomainEvent|AggregateChanged {
        $event = self::occur(
            aggregateId: $productId,
            payload: [
                'product_show_in_menu' => $productShowInMenu->toNative(),
            ],
            metadata: [
                Metadata::AGGREGATE_TYPE => 'product',
            ]
        );

        $event->productId = $productId;
        $event->productShowInMenu = $productShowInMenu;

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

    public function productShowInMenu(): IntegerNumber
    {
        if (is_null__($this->productShowInMenu)) {
            $this->productShowInMenu = IntegerNumber::fromNative($this->payload()['product_show_in_menu']);
        }

        return $this->productShowInMenu;
    }
}

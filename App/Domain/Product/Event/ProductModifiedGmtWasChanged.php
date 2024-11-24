<?php

declare(strict_types=1);

namespace App\Domain\Product\Event;

use App\Domain\Product\ValueObject\ProductId;
use App\Shared\Services\DateTime;
use Codefy\Domain\EventSourcing\AggregateChanged;
use Codefy\Domain\EventSourcing\DomainEvent;
use Codefy\Domain\Metadata;
use DateTimeInterface;
use Qubus\Exception\Data\TypeException;
use Qubus\Support\DateTime\QubusDateTimeImmutable;

use function Qubus\Support\Helpers\is_null__;

final class ProductModifiedGmtWasChanged extends AggregateChanged
{
    private ?ProductId $productId = null;

    private ?DateTimeInterface $productModifiedGmt = null;

    public static function withData(
        ProductId $productId,
        DateTimeInterface $productModifiedGmt
    ): ProductModifiedGmtWasChanged|DomainEvent|AggregateChanged {
        $event = self::occur(
            aggregateId: $productId,
            payload: [
                'product_modified_gmt' => $productModifiedGmt
            ],
            metadata: [
                Metadata::AGGREGATE_TYPE => 'product',
            ]
        );

        $event->productId = $productId;
        $event->productModifiedGmt = $productModifiedGmt;

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

    public function productModifiedGmt(): DateTimeInterface
    {
        if (is_null__($this->productModifiedGmt)) {
            $this->productModifiedGmt = QubusDateTimeImmutable::createFromInterface(
                (new DateTime($this->payload()['product_modified_gmt']))->getDateTime()
            );
        }

        return $this->productModifiedGmt;
    }
}

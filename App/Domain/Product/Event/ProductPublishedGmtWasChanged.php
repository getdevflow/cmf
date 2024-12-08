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

final class ProductPublishedGmtWasChanged extends AggregateChanged
{
    private ?ProductId $productId = null;

    private ?DateTimeInterface $productPublishedGmt = null;

    public static function withData(
        ProductId $productId,
        DateTimeInterface $productPublishedGmt
    ): ProductPublishedGmtWasChanged|DomainEvent|AggregateChanged {
        $event = self::occur(
            aggregateId: $productId,
            payload: [
                'product_published_gmt' => (string) $productPublishedGmt
            ],
            metadata: [
                Metadata::AGGREGATE_TYPE => 'product',
            ]
        );

        $event->productId = $productId;
        $event->productPublishedGmt = $productPublishedGmt;

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

    public function productPublishedGmt(): DateTimeInterface
    {
        if (is_null__($this->productPublishedGmt)) {
            $this->productPublishedGmt = QubusDateTimeImmutable::createFromInterface(
                (new DateTime($this->payload()['product_published_gmt']))->getDateTime()
            );
        }

        return $this->productPublishedGmt;
    }
}

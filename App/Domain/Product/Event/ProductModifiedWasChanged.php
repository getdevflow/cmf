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

final class ProductModifiedWasChanged extends AggregateChanged
{
    private ?ProductId $productId = null;

    private ?DateTimeInterface $productModified = null;

    public static function withData(
        ProductId $productId,
        DateTimeInterface $productModified,
    ): ProductModifiedWasChanged|DomainEvent|AggregateChanged {
        $event = self::occur(
            aggregateId: $productId,
            payload: [
                'product_modified' => $productModified,
            ],
            metadata: [
                Metadata::AGGREGATE_TYPE => 'product',
            ]
        );

        $event->productId = $productId;
        $event->productModified = $productModified;

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

    public function productModified(): DateTimeInterface
    {
        if (is_null__($this->productModified)) {
            $this->productModified = QubusDateTimeImmutable::createFromInterface(
                (new DateTime($this->payload()['product_modified']))->getDateTime()
            );
        }

        return $this->productModified;
    }
}

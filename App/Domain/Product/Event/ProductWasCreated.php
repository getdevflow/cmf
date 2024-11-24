<?php

declare(strict_types=1);

namespace App\Domain\Product\Event;

use App\Domain\Product\ValueObject\ProductId;
use App\Domain\User\ValueObject\UserId;
use App\Shared\Services\DateTime;
use App\Shared\ValueObject\ArrayLiteral;
use Codefy\Domain\Aggregate\AggregateId;
use Codefy\Domain\EventSourcing\AggregateChanged;
use Codefy\Domain\EventSourcing\DomainEvent;
use Codefy\Domain\Metadata;
use DateTimeInterface;
use Qubus\Exception\Data\TypeException;
use Qubus\Support\DateTime\QubusDateTimeImmutable;
use Qubus\ValueObjects\Money\Money;
use Qubus\ValueObjects\Number\IntegerNumber;
use Qubus\ValueObjects\StringLiteral\StringLiteral;

use function Qubus\Support\Helpers\is_null__;

final class ProductWasCreated extends AggregateChanged
{
    private ?ProductId $productId = null;
    private ?StringLiteral $productTitle = null;

    private ?StringLiteral $productSlug = null;

    private ?StringLiteral $productBody = null;

    private ?UserId $productAuthor = null;

    private ?StringLiteral $productSku = null;

    private ?Money $productPrice = null;

    private ?StringLiteral $productPurchaseUrl = null;

    private ?IntegerNumber $productShowInMenu = null;

    private ?IntegerNumber $productShowInSearch = null;

    private ?StringLiteral $productFeaturedImage = null;

    private ?StringLiteral $productStatus = null;

    private ?ArrayLiteral $meta = null;

    private ?DateTimeInterface $productCreated = null;

    private ?DateTimeInterface $productCreatedGmt = null;

    private ?DateTimeInterface $productPublished = null;

    private ?DateTimeInterface $productPublishedGmt = null;

    /**
     * @throws TypeException
     */
    public static function withData(
        ProductId $productId,
        StringLiteral $productTitle,
        StringLiteral $productSlug,
        StringLiteral $productBody,
        UserId $productAuthor,
        StringLiteral $productSku,
        Money $productPrice,
        StringLiteral $productPurchaseUrl,
        IntegerNumber $productShowInMenu,
        IntegerNumber $productShowInSearch,
        StringLiteral $productFeaturedImage,
        StringLiteral $productStatus,
        DateTimeInterface $productCreated,
        DateTimeInterface $productCreatedGmt,
        DateTimeInterface $productPublished,
        DateTimeInterface $productPublishedGmt,
        ?ArrayLiteral $meta = null,
    ): ProductWasCreated|DomainEvent|AggregateChanged {
        $event = self::occur(
            aggregateId: $productId,
            payload: [
                'product_title' => $productTitle->toNative(),
                'product_slug' => $productSlug->toNative(),
                'product_body' => $productBody->toNative(),
                'product_author' => $productAuthor->toNative(),
                'product_sku' => $productSku->toNative(),
                'product_price' => $productPrice->getAmount()->toNative(),
                'product_currency' => $productPrice->getCurrency()->getCode()->toNative(),
                'product_purchase_url' => $productPurchaseUrl->toNative(),
                'product_show_in_menu' => $productShowInMenu->toNative(),
                'product_show_in_search' => $productShowInSearch->toNative(),
                'product_featured_image' => $productFeaturedImage->toNative(),
                'product_status' => $productStatus->toNative(),
                'product_created' => $productCreated->format('Y-m-d H:i:s'),
                'product_created_gmt' => $productCreatedGmt->format('Y-m-d H:i:s'),
                'product_published' => $productPublished->format('Y-m-d H:i:s'),
                'product_published_gmt' => $productPublishedGmt->format('Y-m-d H:i:s'),
                'meta' => $meta->toNative(),
            ],
            metadata: [
                Metadata::AGGREGATE_TYPE => 'product',
            ],
        );

        $event->productId = $productId;
        $event->productTitle = $productTitle;
        $event->productSlug = $productSlug;
        $event->productBody = $productBody;
        $event->productAuthor = $productAuthor;
        $event->productSku = $productSku;
        $event->productPrice = $productPrice;
        $event->productPurchaseUrl = $productPurchaseUrl;
        $event->productShowInMenu = $productShowInMenu;
        $event->productShowInSearch = $productShowInSearch;
        $event->productFeaturedImage = $productFeaturedImage;
        $event->productStatus = $productStatus;
        $event->productCreated = $productCreated;
        $event->productCreatedGmt = $productCreatedGmt;
        $event->productPublished = $productPublished;
        $event->productPublishedGmt = $productPublishedGmt;
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
    public function productTitle(): StringLiteral
    {
        if (is_null__($this->productTitle)) {
            $this->productTitle = StringLiteral::fromNative($this->payload()['product_title']);
        }

        return $this->productTitle;
    }

    /**
     * @throws TypeException
     */
    public function productSlug(): StringLiteral
    {
        if (is_null__($this->productSlug)) {
            $this->productSlug = StringLiteral::fromNative($this->payload()['product_slug']);
        }

        return $this->productSlug;
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

    /**
     * @throws TypeException
     */
    public function productSku(): StringLiteral
    {
        if (is_null__($this->productSku)) {
            $this->productSku = StringLiteral::fromNative($this->payload()['product_sku']);
        }

        return $this->productSku;
    }

    /**
     * @throws TypeException
     */
    public function productPrice(): Money
    {
        if (is_null__($this->productPrice)) {
            $this->productPrice = Money::fromNative(
                $this->payload()['product_price'],
                $this->payload()['product_currency']
            );
        }

        return $this->productPrice;
    }

    public function productPurchaseUrl(): StringLiteral
    {
        if (is_null__($this->productPurchaseUrl)) {
            $this->productPurchaseUrl = StringLiteral::fromNative($this->payload()['product_purchase_url']);
        }

        return $this->productPurchaseUrl;
    }

    public function productShowInMenu(): IntegerNumber
    {
        if (is_null__($this->productShowInMenu)) {
            $this->productShowInMenu = IntegerNumber::fromNative($this->payload()['product_show_in_menu']);
        }

        return $this->productShowInMenu;
    }

    public function productShowInSearch(): IntegerNumber
    {
        if (is_null__($this->productShowInSearch)) {
            $this->productShowInSearch = IntegerNumber::fromNative($this->payload()['product_show_in_search']);
        }

        return $this->productShowInSearch;
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

    /**
     * @throws TypeException
     */
    public function productStatus(): StringLiteral
    {
        if (is_null__($this->productStatus)) {
            $this->productStatus = StringLiteral::fromNative($this->payload()['product_status']);
        }

        return $this->productStatus;
    }

    public function productCreated(): DateTimeInterface
    {
        if (is_null__($this->productCreated)) {
            $this->productCreated = QubusDateTimeImmutable::createFromInterface(
                (new DateTime($this->payload()['product_created']))->getDateTime()
            );
        }

        return $this->productCreated;
    }

    public function productCreatedGmt(): DateTimeInterface
    {
        if (is_null__($this->productCreatedGmt)) {
            $this->productCreatedGmt = QubusDateTimeImmutable::createFromInterface(
                (new DateTime($this->payload()['product_created_gmt']))->getDateTime()
            );
        }

        return $this->productCreatedGmt;
    }

    public function productPublished(): DateTimeInterface
    {
        if (is_null__($this->productPublished)) {
            $this->productPublished = QubusDateTimeImmutable::createFromInterface(
                (new DateTime($this->payload()['product_published']))->getDateTime()
            );
        }

        return $this->productPublished;
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

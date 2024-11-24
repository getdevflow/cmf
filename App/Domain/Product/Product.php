<?php

declare(strict_types=1);

namespace App\Domain\Product;

use App\Domain\Product\Event\ProductAuthorWasChanged;
use App\Domain\Product\Event\ProductBodyWasChanged;
use App\Domain\Product\Event\ProductFeaturedImageWasChanged;
use App\Domain\Product\Event\ProductMetaWasChanged;
use App\Domain\Product\Event\ProductModifiedGmtWasChanged;
use App\Domain\Product\Event\ProductModifiedWasChanged;
use App\Domain\Product\Event\ProductPriceWasChanged;
use App\Domain\Product\Event\ProductPublishedGmtWasChanged;
use App\Domain\Product\Event\ProductPublishedWasChanged;
use App\Domain\Product\Event\ProductPurchaseUrlWasChanged;
use App\Domain\Product\Event\ProductShowInMenuWasChanged;
use App\Domain\Product\Event\ProductShowInSearchWasChanged;
use App\Domain\Product\Event\ProductSkuWasChanged;
use App\Domain\Product\Event\ProductSlugWasChanged;
use App\Domain\Product\Event\ProductStatusWasChanged;
use App\Domain\Product\Event\ProductTitleWasChanged;
use App\Domain\Product\Event\ProductWasCreated;
use App\Domain\Product\Event\ProductWasDeleted;
use App\Domain\Product\ValueObject\ProductId;
use App\Domain\User\ValueObject\UserId;
use App\Shared\ValueObject\ArrayLiteral;
use Codefy\Domain\Aggregate\AggregateRoot;
use Codefy\Domain\Aggregate\EventSourcedAggregate;
use DateTimeInterface;
use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Exception;
use Qubus\ValueObjects\Money\Money;
use Qubus\ValueObjects\Number\IntegerNumber;
use Qubus\ValueObjects\StringLiteral\StringLiteral;

use function Qubus\Support\Helpers\is_null__;

class Product extends EventSourcedAggregate implements AggregateRoot
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

    private ?DateTimeInterface $productModified = null;

    private ?DateTimeInterface $productModifiedGmt = null;

    /**
     * @throws TypeException
     */
    public static function createProduct(
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
    ): Product {
        $product = self::root(aggregateId: $productId);

        $product->recordApplyAndPublishThat(
            ProductWasCreated::withData(
                productId: $productId,
                productTitle: $productTitle,
                productSlug: $productSlug,
                productBody: $productBody,
                productAuthor: $productAuthor,
                productSku: $productSku,
                productPrice: $productPrice,
                productPurchaseUrl: $productPurchaseUrl,
                productShowInMenu: $productShowInMenu,
                productShowInSearch: $productShowInSearch,
                productFeaturedImage: $productFeaturedImage,
                productStatus: $productStatus,
                productCreated: $productCreated,
                productCreatedGmt: $productCreatedGmt,
                productPublished: $productPublished,
                productPublishedGmt: $productPublishedGmt,
                meta: $meta
            )
        );

        return $product;
    }

    /**
     * @throws TypeException
     */
    public static function fromNative(string $productId): Product
    {
        return self::root(aggregateId: ProductId::fromString($productId));
    }

    public function productId(): ProductId
    {
        return $this->productId;
    }

    public function productTitle(): StringLiteral
    {
        return $this->productTitle;
    }

    public function productSlug(): StringLiteral
    {
        return $this->productSlug;
    }

    public function productBody(): StringLiteral
    {
        return $this->productBody;
    }

    public function productAuthor(): UserId
    {
        return $this->productAuthor;
    }

    public function productSku(): StringLiteral
    {
        return $this->productSku;
    }

    public function productPrice(): Money
    {
        return $this->productPrice;
    }

    public function productPurchaseUrl(): StringLiteral
    {
        return $this->productPurchaseUrl;
    }

    public function productShowInMenu(): IntegerNumber
    {
        return $this->productShowInMenu;
    }

    public function productShowInSearch(): IntegerNumber
    {
        return $this->productShowInSearch;
    }

    public function productFeaturedImage(): StringLiteral
    {
        return $this->productFeaturedImage;
    }

    public function productStatus(): StringLiteral
    {
        return $this->productStatus;
    }

    public function productCreated(): DateTimeInterface
    {
        return $this->productCreated;
    }

    public function productCreatedGmt(): DateTimeInterface
    {
        return $this->productCreatedGmt;
    }

    public function productPublished(): DateTimeInterface
    {
        return $this->productPublished;
    }

    public function productPublishedGmt(): DateTimeInterface
    {
        return $this->productPublishedGmt;
    }

    /**
     * @throws Exception
     */
    public function changeProductTitle(StringLiteral $productTitle): void
    {
        if ($productTitle->isEmpty()) {
            throw new Exception(message: 'Product title cannot be empty.');
        }
        if ($productTitle->equals($this->productTitle)) {
            return;
        }
        $this->recordApplyAndPublishThat(
            event: ProductTitleWasChanged::withData(productId: $this->productId, productTitle: $productTitle)
        );
    }

    /**
     * @throws Exception
     */
    public function changeProductSlug(StringLiteral $productSlug): void
    {
        if ($productSlug->isEmpty()) {
            throw new Exception(message: 'Product slug cannot be empty.');
        }
        if ($productSlug->equals($this->productSlug)) {
            return;
        }
        $this->recordApplyAndPublishThat(
            event: ProductSlugWasChanged::withData(productId: $this->productId, productSlug: $productSlug)
        );
    }

    /**
     * @throws Exception
     */
    public function changeProductBody(StringLiteral $productBody): void
    {
        if ($productBody->isEmpty()) {
            throw new Exception(message: 'Product body cannot be empty.');
        }
        if ($productBody->equals($this->productBody)) {
            return;
        }
        $this->recordApplyAndPublishThat(ProductBodyWasChanged::withData($this->productId, $productBody));
    }

    /**
     * @throws Exception
     */
    public function changeProductAuthor(UserId $productAuthor): void
    {
        if ($productAuthor->isEmpty()) {
            throw new Exception(message: 'Product author cannot be empty.');
        }
        if ($productAuthor->equals($this->productAuthor)) {
            return;
        }
        $this->recordApplyAndPublishThat(ProductAuthorWasChanged::withData($this->productId, $productAuthor));
    }

    /**
     * @throws Exception
     */
    public function changeProductSku(StringLiteral $productSku): void
    {
        if ($productSku->isEmpty()) {
            throw new Exception(message: 'Product sku cannot be empty.');
        }
        if ($productSku->equals($this->productSku)) {
            return;
        }
        $this->recordApplyAndPublishThat(ProductSkuWasChanged::withData($this->productId, $productSku));
    }

    /**
     * @throws TypeException
     */
    public function changeProductPrice(Money $productPrice): void
    {
        if ($productPrice->getAmount()->toNative() < 0) {
            return;
        }
        if ($productPrice->equals($this->productPrice)) {
            return;
        }
        $this->recordApplyAndPublishThat(ProductPriceWasChanged::withData($this->productId, $productPrice));
    }

    public function changeProductPurchaseUrl(StringLiteral $productPurchaseUrl): void
    {
        if ($productPurchaseUrl->equals($this->productPurchaseUrl)) {
            return;
        }
        $this->recordApplyAndPublishThat(ProductPurchaseUrlWasChanged::withData($this->productId, $productPurchaseUrl));
    }

    /**
     * @throws Exception
     */
    public function changeProductShowInMenu(IntegerNumber $productShowInMenu): void
    {
        if ($productShowInMenu->toNative() < 0) {
            throw new Exception(message: 'Product show in menu must be an absolute value.');
        }
        if ($productShowInMenu->equals($this->productShowInMenu)) {
            return;
        }
        $this->recordApplyAndPublishThat(ProductShowInMenuWasChanged::withData($this->productId, $productShowInMenu));
    }

    /**
     * @throws Exception
     */
    public function changeProductShowInSearch(IntegerNumber $productShowInSearch): void
    {
        if ($productShowInSearch->toNative() < 0) {
            throw new Exception(message: 'Product show in search must be an absolute value.');
        }
        if ($productShowInSearch->equals($this->productShowInSearch)) {
            return;
        }
        $this->recordApplyAndPublishThat(
            ProductShowInSearchWasChanged::withData($this->productId, $productShowInSearch)
        );
    }

    public function changeProductFeaturedImage(StringLiteral $productFeaturedImage): void
    {
        if ($productFeaturedImage->equals($this->productFeaturedImage)) {
            return;
        }
        $this->recordApplyAndPublishThat(
            ProductFeaturedImageWasChanged::withData($this->productId, $productFeaturedImage)
        );
    }

    /**
     * @throws Exception
     */
    public function changeProductStatus(StringLiteral $productStatus): void
    {
        if ($productStatus->isEmpty()) {
            throw new Exception(message: 'Product status cannot be empty.');
        }
        if ($productStatus->equals($this->productStatus)) {
            return;
        }
        $this->recordApplyAndPublishThat(ProductStatusWasChanged::withData($this->productId, $productStatus));
    }

    public function changeProductMeta(ArrayLiteral $meta): void
    {
        if ($meta->isEmpty()) {
            return;
        }

        if ($meta->equals($this->meta)) {
            return;
        }

        $this->recordApplyAndPublishThat(ProductMetaWasChanged::withData($this->productId, $meta));
    }

    /**
     * @throws Exception
     */
    public function changeProductPublished(DateTimeInterface $productPublished): void
    {
        if (empty($this->productPublished)) {
            throw new Exception(message: 'Product published date cannot be empty.');
        }
        if ($this->productPublished->getTimestamp() === $productPublished->getTimestamp()) {
            return;
        }
        $this->recordApplyAndPublishThat(ProductPublishedWasChanged::withData($this->productId, $productPublished));
    }

    /**
     * @throws Exception
     */
    public function changeProductPublishedGmt(DateTimeInterface $productPublishedGmt): void
    {
        if (empty($this->productPublishedGmt)) {
            throw new Exception(message: 'Product published gmt date cannot be empty.');
        }
        if ($this->productPublishedGmt->getTimestamp() === $productPublishedGmt->getTimestamp()) {
            return;
        }
        $this->recordApplyAndPublishThat(
            ProductPublishedGmtWasChanged::withData($this->productId, $productPublishedGmt)
        );
    }

    public function changeProductModified(DateTimeInterface $productModified): void
    {
        if (
                !is_null__($this->productModified) &&
                ($this->productModified->getTimestamp() === $productModified->getTimestamp())
        ) {
            return;
        }
        $this->recordApplyAndPublishThat(ProductModifiedWasChanged::withData($this->productId, $productModified));
    }

    public function changeProductModifiedGmt(DateTimeInterface $productModifiedGmt): void
    {
        if (
                !is_null__($this->productModifiedGmt) &&
                ($this->productModifiedGmt->getTimestamp() === $productModifiedGmt->getTimestamp())
        ) {
            return;
        }
        $this->recordApplyAndPublishThat(ProductModifiedGmtWasChanged::withData($this->productId, $productModifiedGmt));
    }

    public function changeProductDeleted(ProductId $productId): void
    {
        if ($productId->isEmpty()) {
            return;
        }
        if (!$productId->equals($this->productId)) {
            return;
        }
        $this->recordApplyAndPublishThat(ProductWasDeleted::withData($this->productId));
    }

    /**
     * @throws TypeException
     */
    public function whenProductWasCreated(ProductWasCreated $event): void
    {
        $this->productId = $event->productId();
        $this->productTitle = $event->productTitle();
        $this->productSlug = $event->productSlug();
        $this->productBody = $event->productBody();
        $this->productAuthor = $event->productAuthor();
        $this->productSku = $event->productSku();
        $this->productPrice = $event->productPrice();
        $this->productPurchaseUrl = $event->productPurchaseUrl();
        $this->productShowInMenu = $event->productShowInMenu();
        $this->productShowInSearch = $event->productShowInSearch();
        $this->productFeaturedImage = $event->productFeaturedImage();
        $this->productStatus = $event->productStatus();
        $this->productCreated = $event->productCreated();
        $this->productCreatedGmt = $event->productCreatedGmt();
        $this->productPublished = $event->productPublished();
        $this->productPublishedGmt = $event->productPublishedGmt();
        $this->meta = $event->productMeta();
    }

    /**
     * @throws TypeException
     */
    public function whenProductTitleWasChanged(ProductTitleWasChanged $event): void
    {
        $this->productId = $event->productId();
        $this->productTitle = $event->productTitle();
    }

    /**
     * @throws TypeException
     */
    public function whenProductSlugWasChanged(ProductSlugWasChanged $event): void
    {
        $this->productId = $event->productId();
        $this->productSlug = $event->productSlug();
    }

    /**
     * @throws TypeException
     */
    public function whenProductBodyWasChanged(ProductBodyWasChanged $event): void
    {
        $this->productId = $event->productId();
        $this->productBody = $event->productBody();
    }

    /**
     * @throws TypeException
     */
    public function whenProductAuthorWasChanged(ProductAuthorWasChanged $event): void
    {
        $this->productId = $event->productId();
        $this->productAuthor = $event->productAuthor();
    }

    /**
     * @throws TypeException
     */
    public function whenProductSkuWasChanged(ProductSkuWasChanged $event): void
    {
        $this->productId = $event->productId();
        $this->productSku = $event->productSku();
    }

    /**
     * @throws TypeException
     */
    public function whenProductPriceWasChanged(ProductPriceWasChanged $event): void
    {
        $this->productId = $event->productId();
        $this->productPrice = $event->productPrice();
    }

    /**
     * @throws TypeException
     */
    public function whenProductPurchaseUrlWasChanged(ProductPurchaseUrlWasChanged $event): void
    {
        $this->productId = $event->productId();
        $this->productPurchaseUrl = $event->productPurchaseUrl();
    }

    /**
     * @throws TypeException
     */
    public function whenProductShowInMenuWasChanged(ProductShowInMenuWasChanged $event): void
    {
        $this->productId = $event->productId();
        $this->productShowInMenu = $event->productShowInMenu();
    }

    /**
     * @throws TypeException
     */
    public function whenProductShowInSearchWasChanged(ProductShowInSearchWasChanged $event): void
    {
        $this->productId = $event->productId();
        $this->productShowInSearch = $event->productShowInSearch();
    }

    /**
     * @throws TypeException
     */
    public function whenProductFeaturedImageWasChanged(ProductFeaturedImageWasChanged $event): void
    {
        $this->productId = $event->productId();
        $this->productFeaturedImage = $event->productFeaturedImage();
    }

    /**
     * @throws TypeException
     */
    public function whenProductStatusWasChanged(ProductStatusWasChanged $event): void
    {
        $this->productId = $event->productId();
        $this->productStatus = $event->productStatus();
    }

    /**
     * @throws TypeException
     */
    public function whenProductMetaWasChanged(ProductMetaWasChanged $event): void
    {
        $this->productId = $event->productId();
        $this->meta = $event->productMeta();
    }

    /**
     * @throws TypeException
     */
    public function whenProductPublishedWasChanged(ProductPublishedWasChanged $event): void
    {
        $this->productId = $event->productId();
        $this->productPublished = $event->productPublished();
    }

    /**
     * @throws TypeException
     */
    public function whenProductPublishedGmtWasChanged(ProductPublishedGmtWasChanged $event): void
    {
        $this->productId = $event->productId();
        $this->productPublishedGmt = $event->productPublishedGmt();
    }

    /**
     * @throws TypeException
     */
    public function whenProductModifiedWasChanged(ProductModifiedWasChanged $event): void
    {
        $this->productId = $event->productId();
        $this->productModified = $event->productModified();
    }

    /**
     * @throws TypeException
     */
    public function whenProductModifiedGmtWasChanged(ProductModifiedGmtWasChanged $event): void
    {
        $this->productId = $event->productId();
        $this->productModifiedGmt = $event->productModifiedGmt();
    }

    /**
     * @throws TypeException
     */
    public function whenProductWasDeleted(ProductWasDeleted $event): void
    {
        $this->productId = $event->productId();
    }
}

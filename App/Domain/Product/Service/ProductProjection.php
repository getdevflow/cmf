<?php

declare(strict_types=1);

namespace App\Domain\Product\Service;

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
use Codefy\Domain\EventSourcing\Projection;

interface ProductProjection extends Projection
{
    public function projectWhenProductWasCreated(ProductWasCreated $event): void;

    public function projectWhenProductTitleWasChanged(ProductTitleWasChanged $event): void;

    public function projectWhenProductSlugWasChanged(ProductSlugWasChanged $event): void;

    public function projectWhenProductBodyWasChanged(ProductBodyWasChanged $event): void;

    public function projectWhenProductAuthorWasChanged(ProductAuthorWasChanged $event): void;

    public function projectWhenProductSkuWasChanged(ProductSkuWasChanged $event): void;

    public function projectWhenProductPriceWasChanged(ProductPriceWasChanged $event): void;

    public function projectWhenProductPurchaseUrlWaschanged(ProductPurchaseUrlWasChanged $event): void;

    public function projectWhenProductShowInMenuWasChanged(ProductShowInMenuWasChanged $event): void;

    public function projectWhenProductShowInSearchWasChanged(ProductShowInSearchWasChanged $event): void;

    public function projectWhenProductFeaturedImageWasChanged(ProductFeaturedImageWasChanged $event): void;

    public function projectWhenProductStatusWasChanged(ProductStatusWasChanged $event): void;

    public function projectWhenProductMetaWasChanged(ProductMetaWasChanged $event): void;

    public function projectWhenProductPublishedWasChanged(ProductPublishedWasChanged $event): void;

    public function projectWhenProductPublishedGmtWasChanged(ProductPublishedGmtWasChanged $event): void;

    public function projectWhenProductModifiedWasChanged(ProductModifiedWasChanged $event): void;

    public function projectWhenProductModifiedGmtWasChanged(ProductModifiedGmtWasChanged $event): void;

    public function projectWhenProductWasDeleted(ProductWasDeleted $event): void;
}

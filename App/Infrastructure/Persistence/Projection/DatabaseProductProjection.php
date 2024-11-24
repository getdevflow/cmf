<?php

namespace App\Infrastructure\Persistence\Projection;

use App\Domain\Product\Event\ProductShowInSearchWasChanged;
use App\Domain\Product\Event\ProductSlugWasChanged;
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
use App\Domain\Product\Event\ProductSkuWasChanged;
use App\Domain\Product\Event\ProductStatusWasChanged;
use App\Domain\Product\Event\ProductTitleWasChanged;
use App\Domain\Product\Event\ProductWasCreated;
use App\Domain\Product\Event\ProductWasDeleted;
use App\Domain\Product\Service\ProductProjection;
use App\Infrastructure\Persistence\Database;
use Codefy\CommandBus\Exceptions\CommandPropertyNotFoundException;
use Codefy\Domain\EventSourcing\BaseProjection;
use Codefy\QueryBus\UnresolvableQueryHandlerException;
use Exception as NativeException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Exception;
use Qubus\Expressive\OrmException;
use ReflectionException;

use function App\Shared\Helpers\add_productmeta;
use function App\Shared\Helpers\dfdb;
use function App\Shared\Helpers\update_productmeta;

class DatabaseProductProjection extends BaseProjection implements ProductProjection
{
    protected ?Database $dfdb = null;

    /**
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     * @throws NotFoundExceptionInterface
     */
    public function __construct(?Database $dfdb = null)
    {
        $this->dfdb = $dfdb ?? dfdb();
    }

    /**
     * @param ProductWasCreated $event
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws TypeException
     * @throws CommandPropertyNotFoundException
     * @throws UnresolvableQueryHandlerException
     * @throws Exception
     * @throws NativeException
     */
    public function projectWhenProductWasCreated(ProductWasCreated $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {
                $this->dfdb->qb()
                    ->table(tableName: $this->dfdb->prefix . 'product')
                    ->set([
                        'product_id' => $event->productId()->toNative(),
                        'product_title' => $event->productTitle()->toNative(),
                        'product_slug' => $event->productSlug()->toNative(),
                        'product_body' => $event->productBody() === null ? null : $event->productBody()->toNative(),
                        'product_author' => $event->productAuthor()->toNative(),
                        'product_sku' => $event->productSku()->toNative(),
                        'product_price' => $event->productPrice()->getAmount()->toNative(),
                        'product_currency' => $event->productPrice()->getCurrency()->getCode()->toNative(),
                        'product_purchase_url' => $event->productPurchaseUrl() === null ?
                                null :
                                $event->productPurchaseUrl()->toNative(),
                        'product_show_in_menu' => $event->productShowInMenu()->toNative(),
                        'product_show_in_search' => $event->productShowInSearch()->toNative(),
                        'product_featured_image' => $event->productFeaturedImage()->toNative(),
                        'product_status' => $event->productStatus()->toNative(),
                        'product_created' => $event->productCreated(),
                        'product_created_gmt' => $event->productCreatedGmt(),
                        'product_published' => $event->productPublished(),
                        'product_published_gmt' => $event->productPublishedGmt(),
                    ])
                    ->save();
            });

            if (!$event->productMeta()->isEmpty()) {
                foreach ($event->productMeta()->toNative() as $meta => $value) {
                    add_productmeta($event->aggregateId()->__toString(), $meta, $value);
                }
            }
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }

    /**
     * @param ProductTitleWasChanged $event
     * @return void
     * @throws TypeException
     * @throws NativeException
     */
    public function projectWhenProductTitleWasChanged(ProductTitleWasChanged $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {
                $this->dfdb->qb()
                    ->table(tableName: $this->dfdb->prefix . 'product')
                    ->set([
                            'product_title' => $event->productTitle()->toNative(),
                    ])
                    ->where('product_id = ?', $event->productId()->toNative())
                    ->update();
            });
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }

    /**
     * @param ProductSlugWasChanged $event
     * @return void
     * @throws TypeException
     * @throws NativeException
     */
    public function projectWhenProductSlugWasChanged(ProductSlugWasChanged $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {
                $this->dfdb->qb()
                    ->table(tableName: $this->dfdb->prefix . 'product')
                    ->set([
                            'product_slug' => $event->productSlug()->toNative(),
                    ])
                    ->where('product_id = ?', $event->productId()->toNative())
                    ->update();
            });
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }

    /**
     * @param ProductBodyWasChanged $event
     * @return void
     * @throws TypeException
     * @throws NativeException
     */
    public function projectWhenProductBodyWasChanged(ProductBodyWasChanged $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {
                $this->dfdb->qb()
                    ->table(tableName: $this->dfdb->prefix . 'product')
                    ->set([
                            'product_body' => $event->productBody()->toNative(),
                    ])
                    ->where('product_id = ?', $event->productId()->toNative())
                    ->update();
            });
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }

    /**
     * @param ProductAuthorWasChanged $event
     * @return void
     * @throws TypeException
     * @throws NativeException
     */
    public function projectWhenProductAuthorWasChanged(ProductAuthorWasChanged $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {
                $this->dfdb->qb()
                    ->table(tableName: $this->dfdb->prefix . 'product')
                    ->set([
                            'product_author' => $event->productAuthor()->toNative(),
                    ])
                    ->where('product_id = ?', $event->productId()->toNative())
                    ->update();
            });
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }

    /**
     * @param ProductSkuWasChanged $event
     * @return void
     * @throws TypeException
     * @throws NativeException
     */
    public function projectWhenProductSkuWasChanged(ProductSkuWasChanged $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {
                $this->dfdb->qb()
                    ->table(tableName: $this->dfdb->prefix . 'product')
                    ->set([
                            'product_sku' => $event->productSku()->toNative(),
                    ])
                    ->where('product_id = ?', $event->productId()->toNative())
                    ->update();
            });
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }

    /**
     * @param ProductPriceWasChanged $event
     * @return void
     * @throws TypeException
     * @throws NativeException
     */
    public function projectWhenProductPriceWasChanged(ProductPriceWasChanged $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {
                $this->dfdb->qb()
                    ->table(tableName: $this->dfdb->prefix . 'product')
                    ->set([
                        'product_price' => $event->productPrice()->getAmount()->toNative(),
                        'product_currency' => $event->productPrice()->getCurrency()->getCode()->toNative(),
                    ])
                    ->where('product_id = ?', $event->productId()->toNative())
                    ->update();
            });
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }

    /**
     * @param ProductPurchaseUrlWasChanged $event
     * @return void
     * @throws TypeException
     * @throws NativeException
     */
    public function projectWhenProductPurchaseUrlWaschanged(ProductPurchaseUrlWasChanged $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {
                $this->dfdb->qb()
                        ->table(tableName: $this->dfdb->prefix . 'product')
                        ->set([
                            'product_purchase_url' => $event->productPurchaseUrl()->toNative(),
                        ])
                        ->where('product_id = ?', $event->productId()->toNative())
                        ->update();
            });
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }

    /**
     * @param ProductShowInMenuWasChanged $event
     * @return void
     * @throws TypeException
     * @throws NativeException
     */
    public function projectWhenProductShowInMenuWasChanged(ProductShowInMenuWasChanged $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {
                $this->dfdb->qb()
                        ->table(tableName: $this->dfdb->prefix . 'product')
                        ->set([
                            'product_show_in_menu' => $event->productShowInMenu()->toNative(),
                        ])
                        ->where('product_id = ?', $event->productId()->toNative())
                        ->update();
            });
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }

    /**
     * @param ProductShowInSearchWasChanged $event
     * @return void
     * @throws TypeException
     * @throws NativeException
     */
    public function projectWhenProductShowInSearchWasChanged(ProductShowInSearchWasChanged $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {
                $this->dfdb->qb()
                        ->table(tableName: $this->dfdb->prefix . 'product')
                        ->set([
                            'product_show_in_search' => $event->productShowInSearch()->toNative(),
                        ])
                        ->where('product_id = ?', $event->productId()->toNative())
                        ->update();
            });
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }

    /**
     * @param ProductFeaturedImageWasChanged $event
     * @return void
     * @throws TypeException
     * @throws NativeException
     */
    public function projectWhenProductFeaturedImageWasChanged(ProductFeaturedImageWasChanged $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {
                $this->dfdb->qb()
                        ->table(tableName: $this->dfdb->prefix . 'product')
                        ->set([
                                'product_featured_image' => $event->productFeaturedImage()->toNative(),
                        ])
                        ->where('product_id = ?', $event->productId()->toNative())
                        ->update();
            });
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }

    /**
     * @param ProductStatusWasChanged $event
     * @return void
     * @throws TypeException
     * @throws NativeException
     */
    public function projectWhenProductStatusWasChanged(ProductStatusWasChanged $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {
                $this->dfdb->qb()
                        ->table(tableName: $this->dfdb->prefix . 'product')
                        ->set([
                            'product_status' => $event->productStatus()->toNative(),
                        ])
                        ->where('product_id = ?', $event->productId()->toNative())
                        ->update();
            });
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }

    /**
     * @param ProductMetaWasChanged $event
     * @return void
     * @throws CommandPropertyNotFoundException
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws TypeException
     * @throws UnresolvableQueryHandlerException
     */
    public function projectWhenProductMetaWasChanged(ProductMetaWasChanged $event): void
    {
        if (!$event->productMeta()->isEmpty()) {
            foreach ($event->productMeta()->toNative() as $meta => $value) {
                update_productmeta($event->aggregateId()->__toString(), $meta, $value);
            }
        }
    }

    /**
     * @param ProductPublishedWasChanged $event
     * @return void
     * @throws TypeException
     * @throws NativeException
     */
    public function projectWhenProductPublishedWasChanged(ProductPublishedWasChanged $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {
                $this->dfdb->qb()
                        ->table(tableName: $this->dfdb->prefix . 'product')
                        ->set([
                            'product_published' => $event->productPublished()->format('Y-m-d H:i:s'),
                        ])
                        ->where('product_id = ?', $event->productId()->toNative())
                        ->update();
            });
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }

    /**
     * @param ProductPublishedGmtWasChanged $event
     * @return void
     * @throws TypeException
     * @throws NativeException
     */
    public function projectWhenProductPublishedGmtWasChanged(ProductPublishedGmtWasChanged $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {
                $this->dfdb->qb()
                        ->table(tableName: $this->dfdb->prefix . 'product')
                        ->set([
                            'product_published_gmt' => $event->productPublishedGmt()->format('Y-m-d H:i:s'),
                        ])
                        ->where('product_id = ?', $event->productId()->toNative())
                        ->update();
            });
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }

    /**
     * @param ProductModifiedWasChanged $event
     * @return void
     * @throws TypeException
     * @throws NativeException
     */
    public function projectWhenProductModifiedWasChanged(ProductModifiedWasChanged $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {
                $this->dfdb->qb()
                        ->table(tableName: $this->dfdb->prefix . 'product')
                        ->set([
                            'product_modified' => $event->productModified()->format('Y-m-d H:i:s'),
                        ])
                        ->where('product_id = ?', $event->productId()->toNative())
                        ->update();
            });
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }

    /**
     * @param ProductModifiedGmtWasChanged $event
     * @return void
     * @throws TypeException
     * @throws NativeException
     */
    public function projectWhenProductModifiedGmtWasChanged(ProductModifiedGmtWasChanged $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {
                $this->dfdb->qb()
                        ->table(tableName: $this->dfdb->prefix . 'product')
                        ->set([
                            'product_modified_gmt' => $event->productModifiedGmt()->format('Y-m-d H:i:s'),
                        ])
                        ->where('product_id = ?', $event->productId()->toNative())
                        ->update();
            });
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }

    /**
     * @param ProductWasDeleted $event
     * @return void
     * @throws TypeException
     * @throws NativeException
     */
    public function projectWhenProductWasDeleted(ProductWasDeleted $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {
                $this->dfdb->qb()
                        ->table(tableName: $this->dfdb->prefix . 'product')
                        ->where('product_id = ?', $event->productId()->toNative())
                        ->delete();
            });
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }
}

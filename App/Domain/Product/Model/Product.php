<?php

declare(strict_types=1);

namespace App\Domain\Product\Model;

use App\Infrastructure\Persistence\Cache\ProductCachePsr16;
use App\Infrastructure\Persistence\Database;
use App\Shared\Services\MetaData;
use App\Shared\Services\Registry;
use App\Shared\Services\SimpleCacheObjectCacheFactory;
use App\Shared\Services\Trait\HydratorAware;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Qubus\Exception\Exception;
use ReflectionException;
use stdClass;

use function md5;
use function Qubus\Security\Helpers\esc_html;
use function Qubus\Security\Helpers\purify_html;
use function Qubus\Support\Helpers\convert_array_to_object;
use function Qubus\Support\Helpers\is_null__;

final class Product extends stdClass
{
    use HydratorAware;

    public function __construct(protected ?Database $dfdb = null)
    {
    }

    /**
     * Return only the main product fields.
     *
     * @param string $field The field to query against: 'id', 'ID', 'slug' or 'sku'.
     * @param string $value The field value
     * @return object|false Raw user object
     * @throws Exception
     * @throws ReflectionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws InvalidArgumentException
     */
    public function findBy(string $field, string $value): Product|false
    {
        if ('' === $value) {
            return false;
        }

        $productId = match ($field) {
            'id', 'ID' => $value,
            'slug' => SimpleCacheObjectCacheFactory::make(namespace: $this->dfdb->prefix . 'productslug')
                    ->get(md5($value), ''),
            'sku' => SimpleCacheObjectCacheFactory::make(namespace: $this->dfdb->prefix . 'productsku')
                    ->get(md5($value), ''),
            default => false,
        };

        $dbField = match ($field) {
            'id', 'ID' => 'product_id',
            'slug' => 'product_slug',
            'sku' => 'product_sku',
            default => false,
        };

        $product = null;

        if ('' !== $productId) {
            if (
                $data = SimpleCacheObjectCacheFactory::make(namespace: $this->dfdb->prefix . 'products')
                    ->get(md5($productId))
            ) {
                is_array($data) ? convert_array_to_object($data) : $data;
            }
        }

        if (
                !$data = $this->dfdb->getRow(
                    $this->dfdb->prepare(
                        "SELECT * 
                            FROM {$this->dfdb->prefix}product 
                            WHERE $dbField = ?",
                        [
                            $value
                        ]
                    ),
                    Database::ARRAY_A
                )
        ) {
            return false;
        }

        if (!is_null__($data)) {
            $product = $this->create($data);
            ProductCachePsr16::update($product);
        }

        if (is_array($product)) {
            $product = convert_array_to_object($product);
        }

        return $product;
    }

    /**
     * Create a new instance of Content. Optionally populating it
     * from a data array.
     *
     * @param array $data
     * @return Product
     */
    public function create(array $data = []): Product
    {
        $product = $this->__create();
        if ($data) {
            $product = $this->populate($product, $data);
        }
        return $product;
    }

    /**
     * Create a new Content object.
     *
     * @return Product
     */
    protected function __create(): Product
    {
        return new Product();
    }

    public function populate(Product $product, array $data = []): self
    {
        $product->id = esc_html(string: $data['product_id']) ?? null;
        $product->title = esc_html(string: $data['product_title']) ?? null;
        $product->slug = esc_html(string: $data['product_slug']) ?? null;
        $product->body = purify_html(string: $data['product_body']) ?? null;
        $product->author = esc_html(string: $data['product_author']) ?? null;
        $product->sku = esc_html((string) $data['product_sku']) ?? null;
        $product->price = esc_html(string: (string) $data['product_price']) ?? null;
        $product->currency = esc_html(string: (string) $data['product_currency']) ?? null;
        $product->purchaseUrl = isset($data['product_purchase_url']) ?
        esc_html(string: (string) $data['product_purchase_url']) :
        null;
        $product->showInMenu = esc_html(string: (string) $data['product_show_in_menu']) ?? null;
        $product->showInSearch = esc_html(string: (string) $data['product_show_in_search']) ?? null;
        $product->featuredImage = isset($data['product_featured_image']) ?
        esc_html(string: $data['product_featured_image']) :
        null;
        $product->status = esc_html(string: $data['product_status']) ?? null;
        $product->created = esc_html(string: $data['product_created']) ?? null;
        $product->createdGmt = esc_html(string: $data['product_created_gmt']) ?? null;
        $product->published = esc_html(string: $data['product_published']) ?? null;
        $product->publishedGmt = esc_html(string: $data['product_published_gmt']) ?? null;
        $product->modified = isset($data['product_modified']) ? esc_html(string: $data['product_modified']) : null;
        $product->modifiedGmt = isset($data['product_modified_gmt']) ?
        esc_html(string: $data['product_modified_gmt']) : null;

        return $product;
    }

    /**
     * Magic method for checking the existence of a certain custom field.
     *
     * @param string $key Content meta key to check if set.
     * @return bool Whether the given product meta key is set.
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function __isset(string $key)
    {
        if (isset($this->{$key})) {
            return true;
        }

        return MetaData::factory('productmeta')
                ->exists('product', $this->id, Registry::getInstance()->get('tblPrefix') . $key);
    }

    /**
     * Magic method for accessing custom fields.
     *
     * @param string $key Content meta key to retrieve.
     * @return string Value of the given product meta key (if set). If `$key` is 'id', the product ID.
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function __get(string $key): string
    {
        if (isset($this->{$key})) {
            $value = $this->{$key};
        } else {
            $value = MetaData::factory('productmeta')
                    ->read('product', $this->id, Registry::getInstance()->get('tblPrefix') . $key, true);
        }

        return esc_html($value);
    }

    /**
     * Magic method for setting custom product fields.
     *
     * This method does not update custom fields in the product document. It only stores
     * the value on the Content instance.
     *
     * @param string $key   Content meta key.
     * @param mixed  $value Content meta value.
     */
    public function __set(string $key, mixed $value): void
    {
        $this->{$key} = $value;
    }

    /**
     * Magic method for unsetting a certain custom field.
     *
     * @param string $key Content meta key to unset.
     */
    public function __unset(string $key)
    {
        if (isset($this->{$key})) {
            unset($this->{$key});
        }
    }

    /**
     * Retrieve the value of a property or meta key.
     *
     * Retrieves from the product and productmeta table.
     *
     * @param string $key Property
     * @return string
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function get(string $key): string
    {
        return $this->__get($key);
    }

    /**
     * Determine whether a property or meta key is set
     *
     * Consults the product and productmeta tables.
     *
     * @param string $key Property
     * @return bool
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function hasProp(string $key): bool
    {
        return $this->__isset($key);
    }

    /**
     * Return an array representation.
     *
     * @return array Array representation.
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}

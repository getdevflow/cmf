<?php

declare(strict_types=1);

namespace App\Shared\Helpers;

use App\Domain\Product\Command\DeleteProductCommand;
use App\Domain\Product\Query\FindProductsQuery;
use App\Domain\Product\Command\CreateProductCommand;
use App\Domain\Product\Command\UpdateProductCommand;
use App\Domain\Product\Model\Product;
use App\Domain\Product\ProductError;
use App\Domain\Product\ValueObject\ProductId;
use App\Domain\User\ValueObject\UserId;
use App\Infrastructure\Persistence\Cache\ProductCachePsr16;
use App\Shared\Services\DateTime;
use App\Shared\Services\MetaData;
use App\Shared\Services\Sanitizer;
use App\Shared\Services\Utils;
use App\Shared\ValueObject\ArrayLiteral;
use Codefy\CommandBus\Busses\SynchronousCommandBus;
use Codefy\CommandBus\Containers\ContainerFactory;
use Codefy\CommandBus\Exceptions\CommandCouldNotBeHandledException;
use Codefy\CommandBus\Exceptions\CommandPropertyNotFoundException;
use Codefy\CommandBus\Exceptions\UnresolvableCommandHandlerException;
use Codefy\CommandBus\Odin;
use Codefy\CommandBus\Resolvers\NativeCommandHandlerResolver;
use Codefy\Framework\Factory\FileLoggerFactory;
use Codefy\QueryBus\Busses\SynchronousQueryBus;
use Codefy\QueryBus\Enquire;
use Codefy\QueryBus\Resolvers\NativeQueryHandlerResolver;
use Codefy\QueryBus\UnresolvableQueryHandlerException;
use PDOException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Qubus\Error\Error;
use Qubus\EventDispatcher\ActionFilter\Action;
use Qubus\EventDispatcher\ActionFilter\Filter;
use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Exception;
use Qubus\Support\DateTime\QubusDateTimeImmutable;
use Qubus\ValueObjects\Money\Currency;
use Qubus\ValueObjects\Money\CurrencyCode;
use Qubus\ValueObjects\Money\Money;
use Qubus\ValueObjects\Number\IntegerNumber;
use Qubus\ValueObjects\StringLiteral\StringLiteral;
use ReflectionException;

use function array_map;
use function Codefy\Framework\Helpers\config;
use function is_array;
use function preg_split;
use function Qubus\Security\Helpers\esc_html__;
use function Qubus\Security\Helpers\unslash;
use function Qubus\Support\Helpers\concat_ws;
use function Qubus\Support\Helpers\is_false__;
use function Qubus\Support\Helpers\is_null__;
use function Qubus\Support\Helpers\php_like;
use function str_replace;

/**
 * Retrieve all the products regardless of status.
 *
 * @return array
 * @throws ReflectionException
 * @throws UnresolvableQueryHandlerException
 */
function get_products(): array
{
    $resolver = new NativeQueryHandlerResolver(container: ContainerFactory::make(config: config('querybus.aliases')));
    $enquirer = new Enquire(bus: new SynchronousQueryBus($resolver));

    $query = new FindProductsQuery();

    return $enquirer->execute($query);
}

/**
 * Retrieve all products or a product based on filters.
 *
 * @file App/Shared/Helpers/product.php
 * @param string|null $productSku Product sku.
 * @param int $limit Number of products to show.
 * @param int|null $offset The offset of the first row to be returned.
 * @param string $status Returned unescaped product based on status (all, draft, published, pending, archived)
 * @return array Array of published products or product by particular sku.
 * @throws CommandPropertyNotFoundException
 * @throws ReflectionException
 * @throws UnresolvableQueryHandlerException
 */
function get_all_products_with_filters(
    ?string $productSku = null,
    int $limit = 0,
    int $offset = null,
    string $status = 'all'
): array {
    $resolver = new NativeQueryHandlerResolver(container: ContainerFactory::make(config: config('querybus.aliases')));
    $enquirer = new Enquire(bus: new SynchronousQueryBus($resolver));

    $query = new FindProductsQuery([
        'productSku' => $productSku,
        'limit' => $limit,
        'offset' => $offset,
        'status' => $status,
    ]);

    return $enquirer->execute($query);
}

/**
 * Retrieve product by a given field from the product table.
 *
 * @file App/Shared/Helpers/product.php
 * @param string $field The field to retrieve the product with
 *                      (id = product_id, sku = product_sku, slug = product_slug).
 * @param string $value A value for $field (product_id, product_sku, product_slug).
 * @return false|object
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_product_by(string $field, string $value): false|object
{
    $productdata = (new Product(dfdb()))->findBy($field, $value);

    if (is_false__($productdata)) {
        return false;
    }

    return $productdata;
}

/**
 * Retrieve product by the product id.
 *
 * @file App/Shared/Helpers/product.php
 * @param string $productId
 * @return false|object
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_product_by_id(string $productId): object|false
{
    return get_product_by('id', $productId);
}

/**
 * A function which retrieves product datetime.
 *
 * Purpose of this function is for the `product_datetime`
 * filter.
 *
 * @file App/Shared/Helpers/product.php
 * @param string|null $product
 * @return string Product datetime.
 * @throws ReflectionException
 * @throws Exception
 */
function get_product_datetime(?string $product = null): string
{
    $datetime = concat_ws(
        get_product_date('published', $product),
        get_product_time('published', $product),
        ' ',
    );
    /**
     * Filters the product's datetime.
     *
     * @file App/Shared/Helpers/product.php
     * @param string $datetime  The product's datetime.
     * @param string $productId Product id or product object.
     */
    return Filter::getInstance()->applyFilter('product_datetime', $datetime, $product);
}

/**
 * A function which retrieves product modified datetime.
 *
 * Purpose of this function is for the `product_modified`
 * filter.
 *
 * @file App/Shared/Helpers/product.php
 * @param string $productId Product id.
 * @return string Product modified datetime or '' on failure.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_product_modified(string $productId): string
{
    /** @var Product $product */
    $product = get_product_by_id($productId);

    if (is_false__($product)) {
        return '';
    }

    $format = get_user_datetime_format();

    $modified = get_user_datetime($product->modifiedGmt, $format);

    /**
     * Filters the product date.
     *
     * @file App/Shared/Helpers/product.php
     * @param string $modified The product's modified datetime.
     * @param string $format   Format to return datetime string.
     * @param string $productId Product id or product object.
     */
    return Filter::getInstance()->applyFilter('product_modified', $modified, $format, $product);
}

/**
 * A function which retrieves a product body.
 *
 * Purpose of this function is for the `product_body`
 * filter.
 *
 * @file App/Shared/Helpers/product.php
 * @param string $productId Product id.
 * @return string Product body or '' on failure.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_product_body(string $productId): string
{
    /** @var Product $product */
    $product = get_product_by_id($productId);

    if (is_false__($product)) {
        return '';
    }

    $body = $product->body;
    /**
     * Filters the product date.
     *
     * @file App/Shared/Helpers/product.php
     * @param string $body The product's body content.
     * @param string $productId Product id or product object.
     */
    return Filter::getInstance()->applyFilter('product_body', $body, $productId);
}

/**
 * A function which retrieves a product product_type name.
 *
 * Purpose of this function is for the `product_sku`
 * filter.
 *
 * @file App/Shared/Helpers/product.php
 * @param string $productId Product id.
 * @return string|false Product type name or '' on failure.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_product_sku(string $productId): false|string
{
    /** @var Product $product */
    $product = get_product_by_id($productId);

    if (is_false__($product)) {
        return '';
    }

    $sku = $product->sku;
    /**
     * Filters the product product_sku name.
     *
     * @file App/Shared/Helpers/product.php
     * @param string $sku The product's sku.
     * @param string $productId  Product id.
     */
    return Filter::getInstance()->applyFilter('product_sku', $sku, $productId);
}

/**
 * A function which retrieves a product title.
 *
 * Purpose of this function is for the `product_title`
 * filter.
 *
 * @file App/Shared/Helpers/product.php
 * @param string $productId Product id.
 * @return string Product title or '' on failure.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_product_title(string $productId): string
{
    /** @var Product $product */
    $product = get_product_by_id($productId);

    if (is_false__($product)) {
        return '';
    }

    $title = $product->title;
    /**
     * Filters the product title.
     *
     * @file App/Shared/Helpers/product.php
     * @param string   $title The product's title.
     * @param string $product  Product object.
     */
    return Filter::getInstance()->applyFilter('product_title', $title, $product);
}

/**
 * A function which retrieves a product slug.
 *
 * Purpose of this function is for the `product_slug`
 * filter.
 *
 * @file App/Shared/Helpers/product.php
 * @param string $productId Product id.
 * @return string Product slug or ''.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_product_slug(string $productId): string
{
    /** @var Product $product */
    $product = get_product_by_id($productId);

    if (is_false__($product)) {
        return '';
    }

    $slug = $product->slug;
    /**
     * Filters the product's slug.
     *
     * @file App/Shared/Helpers/product.php
     * @param string $slug The product's slug.
     * @param string $product   Product object.
     */
    return Filter::getInstance()->applyFilter('product_slug', $slug, $product);
}

/**
 * Adds label to product's status.
 *
 * @file App/Shared/Helpers/product.php
 * @param string $status
 * @return string Product status label.
 */
function product_status_label(string $status): string
{
    $label = [
        'published' => 'label-success',
        'draft' => 'label-warning',
        'archived' => 'label-danger'
    ];

    return $label[$status];
}

/**
 * Sanitize product meta value.
 *
 * @file App/Shared/Helpers/product.php
 * @param string $metaKey Meta key.
 * @param mixed $metaValue Meta value to sanitize.
 * @param string $objectSubtype Optional. The subtype of the object type.
 * @return mixed Sanitized $metaValue.
 * @throws Exception
 * @throws ReflectionException
 */
function sanitize_product_meta(string $metaKey, mixed $metaValue, string $objectSubtype = ''): mixed
{
    return sanitize_meta($metaKey, $metaValue, 'product', $objectSubtype);
}

/**
 * Retrieve product meta field for a product.
 *
 * @file App/Shared/Helpers/product.php
 * @param string $productId Product ID.
 * @param string $key Optional. The meta key to retrieve.
 * @param bool $single Optional. Whether to return a single value. Default false.
 * @return mixed Will be an array if $single is false. Will be value of metadata
 *               field if $single is true.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_productmeta(string $productId, string $key = '', bool $single = false): mixed
{
    return MetaData::factory(dfdb()->prefix . 'productmeta')
            ->read('product', $productId, $key, $single);
}

/**
 * Get product metadata by meta ID.
 *
 * @file App/Shared/Helpers/product.php
 * @param string $mid
 * @return array|bool
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_productmeta_by_mid(string $mid): bool|array
{
    return MetaData::factory(dfdb()->prefix . 'productmeta')->readByMid('product', $mid);
}

/**
 * Update product meta field based on product ID.
 *
 * Use the $prevValue parameter to differentiate between meta fields with the
 * same key and product ID.
 *
 * If the meta field for the product does not exist, it will be added.
 *
 * @file App/Shared/Helpers/product.php
 * @param string $productId Product ID.
 * @param string $metaKey Metadata key.
 * @param mixed $metaValue Metadata value. Must be serializable if non-scalar.
 * @param mixed $prevValue Optional. Previous value to check before removing.
 *                         Default empty.
 * @return bool|string Meta ID if the key didn't exist, true on successful update,
 *                     false on failure.
 * @throws CommandPropertyNotFoundException
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 * @throws TypeException
 * @throws UnresolvableQueryHandlerException
 */
function update_productmeta(
    string $productId,
    string $metaKey,
    mixed $metaValue,
    mixed $prevValue = ''
): bool|string {
    return MetaData::factory(dfdb()->prefix . 'productmeta')
            ->update('product', $productId, $metaKey, $metaValue, $prevValue);
}

/**
 * Update product metadata by meta ID.
 *
 * @file App/Shared/Helpers/product.php
 * @param string $mid
 * @param string $metaKey
 * @param string $metaValue
 * @return bool
 * @throws CommandPropertyNotFoundException
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 * @throws TypeException
 * @throws UnresolvableQueryHandlerException
 */
function update_productmeta_by_mid(string $mid, string $metaKey, string $metaValue): bool
{
    $_metaKey = unslash($metaKey);
    $_metaValue = unslash($metaValue);

    return MetaData::factory(dfdb()->prefix . 'productmeta')
            ->updateByMid('product', $mid, $_metaKey, $_metaValue);
}

/**
 * Add metadata field to a product.
 *
 * @file App/Shared/Helpers/product.php
 * @param string $productId Product ID.
 * @param string $metaKey Metadata name.
 * @param mixed $metaValue Metadata value. Must be serializable if non-scalar.
 * @param bool $unique Optional. Whether the same key should not be added.
 *                     Default false.
 * @return false|string Meta ID on success, false on failure.
 * @throws CommandPropertyNotFoundException
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 * @throws TypeException
 * @throws UnresolvableQueryHandlerException
 */
function add_productmeta(string $productId, string $metaKey, mixed $metaValue, bool $unique = false): false|string
{
    return MetaData::factory(dfdb()->prefix . 'productmeta')
            ->create('product', $productId, $metaKey, $metaValue, $unique);
}

/**
 * Remove metadata matching criteria from a product.
 *
 * You can match based on the key, or key and value. Removing based on key and
 * value, will keep from removing duplicate metadata with the same key. It also
 * allows removing all metadata matching key, if needed.
 *
 * @file App/Shared/Helpers/product.php
 * @param string $productId Product ID.
 * @param string $metaKey Metadata name.
 * @param mixed $metaValue Optional. Metadata value. Must be serializable if
 *                         non-scalar. Default empty.
 * @return bool True on success, false on failure.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function delete_productmeta(string $productId, string $metaKey, mixed $metaValue = ''): bool
{
    return MetaData::factory(dfdb()->prefix . 'productmeta')
            ->delete('product', $productId, $metaKey, $metaValue);
}

/**
 * Delete product meta data by meta ID.
 *
 * @file App/Shared/Helpers/product.php
 * @param string $mid
 * @return bool
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function delete_productmeta_by_mid(string $mid): bool
{
    return MetaData::factory(dfdb()->prefix . 'productmeta')->deleteByMid('product', $mid);
}

/**
 * Retrieve product meta fields, based on product ID.
 *
 * The product meta fields are retrieved from the cache where possible,
 * so the function is optimized to be called more than once.
 *
 * @file App/Shared/Helpers/product.php
 * @param string $productId The product's id.
 * @return mixed Product meta for the given product.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_product_custom(string $productId): mixed
{
    return get_productmeta($productId);
}

/**
 * Retrieve meta field names for a product.
 *
 * If there are no meta fields, then nothing (null) will be returned.
 *
 * @file App/Shared/Helpers/product.php
 * @param string $productId The product's id.
 * @return array Array of the keys, if retrieved.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_product_custom_keys(string $productId): array
{
    $custom = get_product_custom($productId);
    if (!is_array($custom)) {
        return [];
    }
    if ($keys = array_keys($custom)) {
        return $keys;
    }

    return [];
}

/**
 * Retrieve values for a custom product field.
 *
 * The parameters must not be considered optional. All the product meta fields
 * will be retrieved and only the meta field key values returned.
 *
 * @file App/Shared/Helpers/product.php
 * @param string $productId The product's id.
 * @param string $key Meta field key.
 * @return array Meta field values or [].
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_product_custom_values(string $productId, string $key): array
{
    $custom = get_product_custom($productId);
    return $custom[$key] ?? [];
}

/**
 * A function which retrieves a product author id.
 *
 * Purpose of this function is for the `product_author_id`
 * filter.
 *
 * @file App/Shared/Helpers/product.php
 * @param string $productId Product id.
 * @return false|string Product author id or false on failure.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_product_author_id(string $productId): false|string
{
    /** @var Product $product */
    $product = get_product_by_id($productId);

    if (is_false__($product)) {
        return '';
    }

    $authorId = $product->author;
    /**
     * Filters the product author id.
     *
     * @file App/Shared/Helpers/product.php
     * @param string $authorId The product's author id.
     * @param object $product Product object.
     */
    return Filter::getInstance()->applyFilter('product_author_id', $authorId, $product);
}

/**
 * A function which retrieves a product author.
 *
 * Purpose of this function is for the `product_author`
 * filter.
 *
 * @file App/Shared/Helpers/product.php
 * @param string $productId Optional Product id or product object.
 * @param bool $reverse If first name should appear first or not. Default is false.
 * @return string|false Product author or false on failure.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_product_author(string $productId, bool $reverse = false): false|string
{
    /** @var Product $product */
    $product = get_product_by_id($productId);

    if (is_false__($product)) {
        return false;
    }

    $author = get_name($product->author, $reverse);
    /**
     * Filters the product author.
     *
     * @file App/Shared/Helpers/product.php
     * @param string $author The product's author.
     * @param object $product Product object.
     */
    return Filter::getInstance()->applyFilter('product_author', $author, $product);
}

/**
 * A function which retrieves a product status.
 *
 * Purpose of this function is for the `product_status`
 * filter.
 *
 * @file App/Shared/Helpers/product.php
 * @param string $productId Product id.
 * @return string|false Product status or false on failure.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_product_status(string $productId): false|string
{
    /** @var Product $product */
    $product = get_product_by_id($productId);

    if (is_false__($product)) {
        return false;
    }

    $status = $product->status;
    /**
     * Filters the product status.
     *
     * @file App/Shared/Helpers/product.php
     * @param string  $status The product's status.
     * @param Product $product Product object.
     */
    return Filter::getInstance()->applyFilter('product_status', $status, $product);
}

/**
 * A function which retrieves product date.
 *
 * Uses `call_user_func_array()` function to return appropriate product date function.
 * Dynamic part is the variable $type, which calls the date function you need.
 *
 * @file App/Shared/Helpers/product.php
 * @param string $type Type of date to return: created, published, modified. Default: published.
 * @param string $productId Product id.
 * @return string Product date.
 */
function get_product_date(string $type = 'published', string $productId = ''): string
{
    return call_user_func_array("App\\Shared\\Helpers\\the_product_{$type}_date", ['Y-m-d',&$productId]);
}

/**
 * A function which retrieves product time.
 *
 * Uses `call_user_func_array()` function to return appropriate product time function.
 * Dynamic part is the variable $type, which calls the date function you need.
 *
 * @file App/Shared/Helpers/product.php
 * @param string $type Type of date to return: created, published, modified. Default: published.
 * @param string $productId Product id.
 * @return string Product time.
 */
function get_product_time(string $type = 'published', string $productId = ''): string
{
    return call_user_func_array("App\\Shared\\Helpers\\the_product_{$type}_time", ['h:i A',&$productId]);
}

/**
 * Retrieves product created date.
 *
 * @file App/Shared/Helpers/product.php
 * @param string $productId Product id.
 * @param string $format Format to use for retrieving the date the product was created.
 *                       Accepts 'G', 'U', or php date format value specified
 *                       in 'date_format' option. Default 'U'.
 * @param bool $gmt Whether to use GMT. Default false.
 * @param bool $translate Whether the returned string should be translated. Default false.
 * @return string Formatted product created date string or Unix timestamp
 *                if $format is 'U' or 'G'. '' on failure.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_product_created_date(
    string $productId,
    string $format = 'U',
    bool $gmt = false,
    bool $translate = false
): string {
    /** @var Product $product */
    $product = get_product_by_id($productId);

    if (is_false__($product)) {
        return '';
    }

    $date = new DateTime();

    if ($gmt) {
        $theDate = get_user_datetime($product->createdGmt);
    } else {
        $theDate = $product->created;
    }

    $theDate = $date->db2Date($format, $theDate, $translate);

    if (is_false__($theDate)) {
        return '';
    }

    /**
     * Filters the product created date.
     *
     * @file App/Shared/Helpers/product.php
     * @param string $theDate The product's formatted date.
     * @param bool   $format Format to use for retrieving the date the product was written.
     *                       Accepts 'G', 'U', or php date format. Default 'U'.
     * @param bool   $gmt    Whether to retrieve the GMT date. Default false.
     */
    return Filter::getInstance()->applyFilter('get_product_created_date', $theDate, $format, $gmt);
}

/**
 * Retrieves product created date.
 *
 * @file App/Shared/Helpers/product.php
 * @param string $productId Product id.
 * @param string $format Format to use for retrieving the date the product was created.
 *                       Accepts 'G', 'U', or php date format value specified
 *                       in 'date_format' option. Default empty.
 * @return string Formatted product created date string or Unix timestamp
 *                if $format is 'U' or 'G'. '' on failure.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function the_product_created_date(string $productId, string $format = ''): string
{
    /** @var Product $product */
    $product = get_product_by_id($productId);

    if (is_false__($product)) {
        return '';
    }

    if ('' === $format) {
        $theDate = get_product_created_date(
            $productId,
            get_user_date_format(),
            true,
            true
        );
    } else {
        $theDate = get_product_created_date($productId, $format, true, true);
    }

    /**
     * Filters the date the product was written.
     *
     * @file App/Shared/Helpers/product.php
     * @param string    $theDate The formatted date.
     * @param string    $format  Format to use for retrieving the date the product was written.
     *                           Accepts 'G', 'U', or php date format value specified
     *                           in 'date_format' option. Default empty.
     * @param Product  $product  Product object.
     */
    return Filter::getInstance()->applyFilter('product_created_date', $theDate, $format, $product);
}

/**
 * A function which retrieves product created time.
 *
 * Purpose of this function is for the `product_created_time`
 * filter.
 *
 * @file App/Shared/Helpers/product.php
 * @param string $productId Product id.
 * @param string $format Format to use for retrieving the time the product was created.
 *                        Accepts 'G', 'U', or php date format value specified
 *                        in 'date_format' option. Default 'U'.
 * @param bool $gmt Whether to use GMT. Default false.
 * @param bool $translate Whether the returned string should be translated. Default false.
 * @return string Formatted product created time string or Unix timestamp
 *                          if $format is 'U' or 'G'. '' on failure.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_product_created_time(
    string $productId,
    string $format = 'U',
    bool $gmt = false,
    bool $translate = false
): string {
    /** @var Product $product */
    $product = get_product_by_id($productId);

    if (is_false__($product)) {
        return '';
    }

    $date = new DateTime();

    if ($gmt) {
        $theTime = get_user_datetime($product->createdGmt);
    } else {
        $theTime = $product->created;
    }

    $theTime = $date->db2Date($format, $theTime, $translate);

    if (is_false__($theTime)) {
        return '';
    }

    /**
     * Filters the product created time.
     *
     * @file App/Shared/Helpers/product.php
     * @param string $theTime The product's formatted time.
     * @param bool   $format   Format to use for retrieving the time the product was written.
     *                         Accepts 'G', 'U', or php date format. Default 'U'.
     * @param bool   $gmt      Whether to retrieve the GMT time. Default false.
     */
    return Filter::getInstance()->applyFilter('get_product_created_time', $theTime, $format, $gmt);
}

/**
 * Retrieves product created time.
 *
 * @file App/Shared/Helpers/product.php
 * @param string $productId Product id.
 * @param string $format Format to use for retrieving the time the product was written.
 *                       Accepts 'G', 'U', or php date format value specified
 *                       in 'time_format' option. Default empty.
 * @return string Formatted product created time string or Unix timestamp
 *                if $format is 'U' or 'G'. '' on failure.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function the_product_created_time(string $productId, string $format = ''): string
{
    /** @var Product $product */
    $product = get_product_by_id($productId);

    if (is_false__($product)) {
        return '';
    }

    if ('' === $format) {
        $theTime = get_product_created_time(
            $productId,
            get_user_time_format(),
            true,
            true
        );
    } else {
        $theTime = get_product_created_time($productId, $format, true, true);
    }

    if (is_false__($theTime)) {
        return '';
    }

    /**
     * Filters the time the product was written.
     *
     * @file App/Shared/Helpers/product.php
     * @param string    $theTime The formatted time.
     * @param string    $format  Format to use for retrieving the time the product was written.
     *                           Accepts 'G', 'U', or php date format value specified
     *                           in 'time_format' option. Default empty.
     * @param object    $product Product object.
     */
    return Filter::getInstance()->applyFilter('product_created_time', $theTime, $format, $product);
}

/**
 * A function which retrieves product published date.
 *
 * @file App/Shared/Helpers/product.php
 * @param string $productId Product id.
 * @param string $format Format to use for retrieving the date the product was published.
 *                        Accepts 'G', 'U', or php date format value specified
 *                        in 'date_format' option. Default 'U'.
 * @param bool $gmt Whether to use GMT. Default false.
 * @param bool $translate Whether the returned string should be translated. Default false.
 * @return string Formatted product published date string or Unix timestamp
 *                if $format is 'U' or 'G'. '' on failure.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_product_published_date(
    string $productId,
    string $format = 'U',
    bool $gmt = false,
    bool $translate = false
): string {
    /** @var Product $product */
    $product = get_product_by_id($productId);

    if (is_false__($product)) {
        return '';
    }

    $date = new DateTime();

    if ($gmt) {
        $theDate = get_user_datetime($product->publishedGmt);
    } else {
        $theDate = $product->published;
    }

    $theDate = $date->db2Date($format, $theDate, $translate);

    if (is_false__($theDate)) {
        return '';
    }

    /**
     * Filters the product published date.
     *
     * @file App/Shared/Helpers/product.php
     * @param string $theDate The product's formatted date.
     * @param bool $format Format to use for retrieving the date the product was published.
     *                     Accepts 'G', 'U', or php date format. Default 'U'.
     * @param bool   $gmt  Whether to retrieve the GMT date. Default false.
     */
    return Filter::getInstance()->applyFilter('get_product_published_date', $theDate, $format, $gmt);
}

/**
 * Retrieves product published date.
 *
 * @file App/Shared/Helpers/product.php
 * @param string $productId Product id.
 * @param string $format Format to use for retrieving the date the product was published.
 *                       Accepts 'G', 'U', or php date format value specified
 *                       in 'date_format' option. Default empty.
 * @return string Formatted product published date string or Unix timestamp
 *                if $format is 'U' or 'G'. '' on failure.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function the_product_published_date(string $productId, string $format = ''): string
{
    /** @var Product $product */
    $product = get_product_by_id($productId);

    if (is_false__($product)) {
        return '';
    }

    if ('' === $format) {
        $theDate = get_product_published_date(
            $productId,
            get_user_date_format(),
            true,
            true
        );
    } else {
        $theDate = get_product_published_date($productId, $format, true, true);
    }

    if (is_false__($theDate)) {
        return '';
    }

    /**
     * Filters the time the product was written.
     *
     * @file App/Shared/Helpers/product.php
     * @param string    $theDate The formatted date.
     * @param string    $format   Format to use for retrieving the date the product was published.
     *                            Accepts 'G', 'U', or php date format value specified
     *                            in 'date_format' option. Default empty.
     * @param object    $product  Product object.
     */
    return Filter::getInstance()->applyFilter('product_published_date', $theDate, $format, $product);
}

/**
 * A function which retrieves product published time.
 *
 * @file App/Shared/Helpers/product.php
 * @param string $productId Product id.
 * @param string $format Format to use for retrieving the time the product was published.
 *                        Accepts 'G', 'U', or php date format value specified
 *                        in 'date_format' option. Default 'U'.
 * @param bool $gmt Whether to use GMT. Default false.
 * @param bool $translate Whether the returned string should be translated. Default false.
 * @return string Formatted product published time string or Unix timestamp
 *                if $format is 'U' or 'G'. '' on failure.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_product_published_time(
    string $productId,
    string $format = 'U',
    bool $gmt = false,
    bool $translate = false
): string {
    /** @var Product $product */
    $product = get_product_by_id($productId);

    if (is_false__($product)) {
        return '';
    }

    $date = new DateTime();

    if ($gmt) {
        $theTime = get_user_datetime($product->publishedGmt);
    } else {
        $theTime = $product->published;
    }

    $theTime = $date->db2Date($format, $theTime, $translate);

    if (is_false__($theTime)) {
        return '';
    }

    /**
     * Filters the product published time.
     *
     * @file App/Shared/Helpers/product.php
     * @param string $theTime The product's formatted time.
     * @param bool   $format   Format to use for retrieving the time the product was written.
     *                         Accepts 'G', 'U', or php date format. Default 'U'.
     * @param bool   $gmt      Whether to retrieve the GMT time. Default false.
     */
    return Filter::getInstance()->applyFilter('get_product_published_time', $theTime, $format, $gmt);
}

/**
 * Retrieves product published time.
 *
 * @file App/Shared/Helpers/product.php
 * @param string $productId Product id.
 * @param string $format Format to use for retrieving the time the product was published.
 *                       Accepts 'G', 'U', or php date format value specified
 *                       in 'time_format' option. Default empty.
 * @return string Formatted product published time string or Unix timestamp
 *                if $format is 'U' or 'G'. '' on failure.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function the_product_published_time(string $productId, string $format = ''): string
{
    $product = get_product_by_id($productId);

    if (is_false__($product)) {
        return '';
    }

    if ('' === $format) {
        $theTime = get_product_published_time(
            $productId,
            get_user_time_format(),
            true,
            true
        );
    } else {
        $theTime = get_product_published_time($productId, $format, true, true);
    }

    if (is_false__($theTime)) {
        return '';
    }

    /**
     * Filters the time the product was published.
     *
     * @file App/Shared/Helpers/product.php
     * @param string    $theTime  The formatted time.
     * @param string    $format   Format to use for retrieving the time the product was published.
     *                            Accepts 'G', 'U', or php date format value specified
     *                            in 'time_format' option. Default empty.
     * @param object    $product  Product object.
     */
    return Filter::getInstance()->applyFilter('product_published_time', $theTime, $format, $product);
}

/**
 * A function which retrieves product modified date.
 *
 * @file App/Shared/Helpers/product.php
 * @param string $productId Product id.
 * @param string $format Format to use for retrieving the date the product was modified.
 *                        Accepts 'G', 'U', or php date format value specified
 *                        in 'date_format' option. Default 'U'.
 * @param bool $gmt Whether to use GMT. Default false.
 * @param bool $translate Whether the returned string should be translated. Default false.
 * @return string Formatted product modified date string or Unix timestamp
 *                if $format is 'U' or 'G'. '' on failure.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_product_modified_date(
    string $productId,
    string $format = 'U',
    bool $gmt = false,
    bool $translate = false
): string {
    /** @var Product $product */
    $product = get_product_by_id($productId);

    if (is_false__($product)) {
        return '';
    }

    $date = new DateTime();

    if ($gmt) {
        $theDate = get_user_datetime($product->modifiedGmt);
    } else {
        $theDate = $product->modified;
    }

    $theDate = $date->db2Date($format, $theDate, $translate);

    if (is_false__($theDate)) {
        return '';
    }

    /**
     * Filters the product modified date.
     *
     * @file App/Shared/Helpers/product.php
     * @param string $theDate The product's formatted date.
     * @param bool   $format  Format to use for retrieving the date the product was published.
     *                        Accepts 'G', 'U', or php date format. Default 'U'.
     * @param bool   $gmt     Whether to retrieve the GMT date. Default false.
     */
    return Filter::getInstance()->applyFilter('get_product_modified_date', $theDate, $format, $gmt);
}

/**
 * Retrieves product published date.
 *
 * @file App/Shared/Helpers/product.php
 * @param string $productId Product id.
 * @param string $format Format to use for retrieving the date the product was published.
 *                       Accepts 'G', 'U', or php date format value specified
 *                       in 'date_format' option. Default empty.
 * @return string Formatted product modified date string or Unix timestamp
 *                if $format is 'U' or 'G'. '' on failure.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function the_product_modified_date(string $productId, string $format = ''): string
{
    /** @var Product $product */
    $product = get_product_by_id($productId);

    if (is_false__($product)) {
        return '';
    }

    if ('' === $format) {
        $theDate = get_product_modified_date(
            $productId,
            get_user_date_format(),
            true,
            true
        );
    } else {
        $theDate = get_product_modified_date($productId, $format, true, true);
    }

    if (is_false__($theDate)) {
        return '';
    }

    /**
     * Filters the date the product was modified.
     *
     * @file App/Shared/Helpers/product.php
     * @param string    $theDate The formatted date.
     * @param string    $format  Format to use for retrieving the date the product was modified.
     *                           Accepts 'G', 'U', or php date format value specified
     *                           in 'date_format' option. Default empty.
     * @param object    $product Product object.
     */
    return Filter::getInstance()->applyFilter('product_modified_date', $theDate, $format, $product);
}

/**
 * A function which retrieves product modified time.
 *
 * @file App/Shared/Helpers/product.php
 * @param string $productId Product id.
 * @param string $format Format to use for retrieving the time the product was modified.
 *                       Accepts 'G', 'U', or php date format value specified
 *                       in 'date_format' option. Default 'U'.
 * @param bool $gmt Whether to use GMT. Default false.
 * @param bool $translate Whether the returned string should be translated. Default false.
 * @return string Formatted product modified time string or Unix timestamp
 *                if $format is 'U' or 'G'. '' on failure.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_product_modified_time(
    string $productId,
    string $format = 'U',
    bool $gmt = false,
    bool $translate = false
): string {
    /** @var Product $product */
    $product = get_product_by_id($productId);

    if (is_false__($product)) {
        return '';
    }

    $date = new DateTime();

    if ($gmt) {
        $theTime = get_user_datetime($product->modifiedGmt);
    } else {
        $theTime = $product->modified;
    }

    $theTime = $date->db2Date($format, $theTime, $translate);

    if (is_false__($theTime)) {
        return '';
    }

    /**
     * Filters the product modified time.
     *
     * @file App/Shared/Helpers/product.php
     * @param string $theTime The product's formatted time.
     * @param bool   $format   Format to use for retrieving the time the product was modified.
     *                         Accepts 'G', 'U', or php date format. Default 'U'.
     * @param bool   $gmt      Whether to retrieve the GMT time. Default false.
     */
    return Filter::getInstance()->applyFilter('get_product_modified_time', $theTime, $format, $gmt);
}

/**
 * Retrieves product modified time.
 *
 * @file App/Shared/Helpers/product.php
 * @param string $productId Product id.
 * @param string $format Format to use for retrieving the time the product was modified.
 *                       Accepts 'G', 'U', or php date format value specified
 *                       in 'time_format' option. Default empty.
 * @return string Formatted product modified time string or Unix timestamp
 *                if $format is 'U' or 'G'. '' on failure.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function the_product_modified_time(string $productId, string $format = ''): string
{
    /** @var Product $product */
    $product = get_product_by_id($productId);

    if (is_false__($product)) {
        return '';
    }

    if ('' === $format) {
        $theTime = get_product_modified_time(
            $productId,
            get_user_time_format(),
            true,
            true
        );
    } else {
        $theTime = get_product_modified_time($productId, $format, true, true);
    }

    if (is_false__($theTime)) {
        return '';
    }

    /**
     * Filters the time the product was modified.
     *
     * @file App/Shared/Helpers/product.php
     * @param string    $theTime The formatted time.
     * @param string    $format  Format to use for retrieving the time the product was modified.
     *                           Accepts 'G', 'U', or php date format value specified
     *                           in 'time_format' option. Default empty.
     * @param object    $product Product object.
     */
    return Filter::getInstance()->applyFilter('product_modified_time', $theTime, $format, $product);
}

/**
 * A function which retrieves product show in menu.
 *
 * Purpose of this function is for the `product_show_in_menu`
 * filter.
 *
 * @file App/Shared/Helpers/product.php
 * @param string $productId Product id.
 * @return int Product show in menu integer or 0 on failure.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_product_show_in_menu(string $productId): int
{
    /** @var Product $product */
    $product = get_product_by_id($productId);

    if (is_false__($product)) {
        return (int) 0;
    }

    $menu = $product->showInMenu;
    /**
     * Filters the product show in menu.
     *
     * @file App/Shared/Helpers/product.php
     * @param int    $menu      The product's show in menu option.
     * @param string $productId The product ID.
     */
    return Filter::getInstance()->applyFilter('product_show_in_menu', (int) $menu, $productId);
}

/**
 * A function which retrieves product show in search.
 *
 * Purpose of this function is for the `product_show_in_search`
 * filter.
 *
 * @file App/Shared/Helpers/product.php
 * @param string $productId Product id.
 * @return int Product show in search integer or 0 on failure.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_product_show_in_search(string $productId): int
{
    $product = get_product_by_id($productId);

    if (is_false__($product)) {
        return (int) 0;
    }

    $search = $product->showInSearch;
    /**
     * Filters the product show in search.
     *
     * @file App/Shared/Helpers/product.php
     * @param int    $search    The product's show in search option.
     * @param string $productId The product ID.
     */
    return Filter::getInstance()->applyFilter('product_show_in_search', (int) $search, $productId);
}

/**
 * Creates a unique product slug.
 *
 * @file App/Shared/Helpers/product.php
 * @param string $originalSlug Original slug of product.
 * @param string $originalTitle Original title of product.
 * @param string|null $productId Unique product id or null.
 * @return string Unique product slug.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function cms_unique_product_slug(
    string $originalSlug,
    string $originalTitle,
    ?string $productId = null,
): string {
    if (is_null__($productId)) {
        $productSlug = cms_slugify($originalTitle, 'product');
    } elseif (if_product_slug_exists($productId, $originalSlug)) {
        $productSlug = cms_slugify($originalTitle, 'product');
    } else {
        $productSlug = $originalSlug;
    }
    /**
     * Filters the unique product slug before returned.
     *
     * @file App/Shared/Helpers/product.php
     * @param string    $productSlug   Unique product slug.
     * @param string    $originalSlug  The product's original slug.
     * @param string    $originalTitle The product's original title before slugified.
     * @param string    $productId     The product's unique id.
     */
    return Filter::getInstance()->applyFilter(
        'cms_unique_product_slug',
        $productSlug,
        $originalSlug,
        $originalTitle,
        $productId,
    );
}

/**
 * Insert or update a product.
 *
 * All the `$productdata` array fields have filters associated with the values. The filters
 * have the prefix 'pre_' followed by the field name. An example using 'product_status' would have
 * the filter called, 'pre_product_status' that can be hooked into.
 *
 * @file App/Shared/Helpers/product.php
 * @param array|ServerRequestInterface|Product $productdata An array of data that is used for insert or update.
 *
 *      @type string $productTitle The product's title.
 *      @type string $productBody The product's body.
 *      @type string $productSlug The product's slug.
 *      @type string $productAuthor The product's author.
 *      @type string $productSku The product's parent.
 *      @type string $productPrice The product's price.
 *      @type string $productCurrency The product's currency.
 *      @type string $productPurchaseUrl The product's purchase url.
 *      @type string $productShowInMenu Whether to show product in menu.
 *      @type string $productShowInSearch Whether to show product in search.
 *      @type string $productRelativeUrl The product's relative url.
 *      @type string $productFeaturedImage THe product's featured image.
 *      @type string $productStatus THe product's status.
 *      @type string $productPublished Timestamp describing the moment when the product
 *                                     was published. Defaults to Y-m-d h:i A.
 * @return Error|string|null The newly created product's product_id or throws an error or returns null
 *                     if the product could not be created or updated.
 * @throws CommandCouldNotBeHandledException
 * @throws CommandPropertyNotFoundException
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws ReflectionException
 * @throws TypeException
 * @throws UnresolvableCommandHandlerException
 * @throws UnresolvableQueryHandlerException
 * @throws ContainerExceptionInterface
 * @throws NotFoundExceptionInterface
 */
function cms_insert_product(array|ServerRequestInterface|Product $productdata): Error|string|null
{
    $userId = get_current_user_id();

    $defaults = [
        'title' => '',
        'body' => '',
        'author' => $userId,
        'sku' => '',
        'showInMenu' => '0',
        'showInSearch' => '0',
        'featuredImage' => '',
        'status' => 'draft'
    ];

    if ($productdata instanceof ServerRequestInterface) {
        $productdata = $productdata->getParsedBody();
    } elseif ($productdata instanceof Product) {
        $productdata = $productdata->toArray();
    }

    $productdata = Utils::parseArgs($productdata, $defaults);

    // Are we updating or creating?
    if (!empty($productdata['id']) && !is_false__(get_product_by_id($productdata['id']))) {
        $update = true;
        $productId = new ProductId($productdata['id']);
        /** @var Product $productBefore */
        $productBefore = get_product_by('id', $productId->toNative());

        if (is_false__($productBefore)) {
            return new ProductError(message: esc_html__(string: 'Invalid product id.', domain: 'devflow'));
        }

        $previousStatus = get_product_status($productId->toNative());
        /**
         * Fires immediately before product is inserted into the product document.
         *
         * @param string $previousStatus Status of the product before it is created or updated.
         * @param string $productId      The product's product_id.
         * @param bool   $update         Whether this is an existing product or a new product.
         */
        Action::getInstance()->doAction('product_previous_status', $previousStatus, $productId->toNative(), $update);

        /**
         * Create new product object.
         */
        $product = new Product();
        $product->id = $productId->toNative();
    } else {
        $update = false;
        $productId = new ProductId();
        $previousStatus = 'new';
        /**
         * Fires immediately before a product is inserted into the product document.
         *
         * @param string $previousStatus Status of the product before it is created or updated.
         * @param string $productId      The product's product_id.
         * @param bool   $update         Whether this is an existing product or a new product.
         */
        Action::getInstance()->doAction('product_previous_status', $previousStatus, $productId->toNative(), $update);

        /**
         * Create new product object.
         */
        $product = new Product();
        $product->id = $productId->toNative();
    }

    if (isset($productdata['title'])) {
        $productTitle = $productdata['title'];
    } else {
        /**
         * For an update, don't modify the title if it
         * wasn't supplied as an argument.
         */
        $productTitle = $productBefore->title;
    }

    $rawProductSku = $productdata['sku'];
    $sanitizedProductSku = Sanitizer::item($rawProductSku);
    /**
     * Filters a product's type before the product is created or updated.
     *
     * @param string $sanitizedProductType Product type after it has been sanitized.
     * @param string $rawProductType The product's product type.
     */
    $productSku = Filter::getInstance()->applyFilter(
        'pre_product_sku',
        $sanitizedProductSku,
        $rawProductSku
    );
    $product->sku = $productSku;

    $rawProductTitle = $productTitle;
    $sanitizedProductTitle = Sanitizer::item($rawProductTitle);
    /**
     * Filters a product's title before created/updated.
     *
     * @param string $sanitizedProductTitle Product title after it has been sanitized.
     * @param string $rawProductTitle The product's title.
     */
    $productTitle = Filter::getInstance()->applyFilter(
        'pre_product_title',
        (string) $sanitizedProductTitle,
        (string) $rawProductTitle
    );
    $product->title = $productTitle;

    if (isset($productdata['slug'])) {
        /**
         * cms_unique_product_slug will take the original slug supplied and check
         * to make sure that it is unique. If not unique, it will make it unique
         * by adding a number at the end.
         */
        $productSlug = cms_unique_product_slug(
            $productdata['slug'],
            $productTitle,
            $productId->toNative(),
        );
    } else {
        /**
         * For an update, don't modify the slug if it
         * wasn't supplied as an argument.
         */
        $productSlug = $productBefore->slug;
    }

    $rawProductSlug = $productSlug;
    $sanitizedProductSlug = Sanitizer::item($rawProductSlug);
    /**
     * Filters a product's slug before created/updated.
     *
     * @param string $sanitizedProductSlug Product slug after it has been sanitized.
     * @param string $rawProductSlug The product's slug.
     */
    $productSlug = Filter::getInstance()->applyFilter(
        'pre_product_slug',
        (string) $sanitizedProductSlug,
        (string) $rawProductSlug
    );
    $product->slug = $productSlug;

    $rawProductBody = $productdata['body'];
    /**
     * Filters a product's body before created/updated.
     *
     * @param string $rawProductSlug The product's slug.
     */
    $productBody = Filter::getInstance()->applyFilter(
        'pre_product_body',
        $rawProductBody
    );
    $product->body = $productBody;

    /**
     * Check for product author
     *
     * @param string $productAuthor Product author id.
     */
    $productAuthor = $productdata['author'];

    if ($productAuthor === '' || $productAuthor === null) {
        return new ProductError(
            message: esc_html__(string: 'Product author cannot be null or empty.', domain: 'devflow')
        );
    }

    $product->author = $productAuthor;

    $rawProductSku = $productdata['sku'];
    $sanitizedProductSku = Sanitizer::item($rawProductSku);
    /**
     * Filters a product's sku before the product is created or updated.
     *
     * @param string $sanitizedProductSku Product sku after it has been sanitized.
     * @param string $rawProductSku The product's sku.
     */
    $productSku = Filter::getInstance()->applyFilter(
        'pre_product_sku',
        $sanitizedProductSku,
        $rawProductSku
    );
    $product->sku = $productSku;

    $rawProductPrice = $productdata['price'];
    $sanitizedProductPrice = Sanitizer::item($rawProductPrice, 'float');
    /**
     * Filters a product's price before the product is created or updated.
     *
     * @param string $sanitizedProductPrice Product price after it has been sanitized.
     * @param string $rawProductPrice The product's price.
     */
    $productPrice = Filter::getInstance()->applyFilter(
        'pre_product_price',
        (int) $sanitizedProductPrice,
        (int) $rawProductPrice
    );
    $product->price = $productPrice;

    $rawProductCurrency = $productdata['currency'];
    $sanitizedProductCurrency = Sanitizer::item($rawProductCurrency);
    /**
     * Filters a product's currency before the product is created or updated.
     *
     * @param string $sanitizedProductCurrency Product currency after it has been sanitized.
     * @param string $rawProductCurrency The product's currency.
     */
    $productCurrency = Filter::getInstance()->applyFilter(
        'pre_product_currency',
        $sanitizedProductCurrency,
        $rawProductCurrency
    );
    $product->currency = $productCurrency;

    $rawProductPurchaseUrl = $productdata['purchaseUrl'];
    $sanitizedProductPurchaseUrl = Sanitizer::item($rawProductPurchaseUrl);
    /**
     * Filters a product's purchase url before the product is created or updated.
     *
     * @param string $sanitizedProductPurchaseUrl Product purchase url after it has been sanitized.
     * @param string $rawProductPurchaseUrl The product's purchase url.
     */
    $productPurchaseUrl = Filter::getInstance()->applyFilter(
        'pre_product_purchase_url',
        $sanitizedProductPurchaseUrl,
        $rawProductPurchaseUrl
    );
    $product->purchaseUrl = $productPurchaseUrl;

    $rawProductShowInMenu = $productdata['showInMenu'];
    $sanitizedProductShowInMenu = Sanitizer::item($rawProductShowInMenu, 'int');
    /**
     * Filters a product's show in menu before the product is created or updated.
     *
     * @param string $sanitizedProductShowInMenu Product show in menu after it has been sanitized.
     * @param int $rawProductShowInMenu The product's show in menu.
     */
    $productShowInMenu = Filter::getInstance()->applyFilter(
        'pre_product_show_in_menu',
        (int) $sanitizedProductShowInMenu,
        (int) $rawProductShowInMenu
    );
    $product->showInMenu = $productShowInMenu;

    $rawProductShowInSearch = $productdata['showInSearch'];
    $sanitizedProductShowInSearch = Sanitizer::item($rawProductShowInSearch, 'int');
    /**
     * Filters a product's show in search before the product is created or updated.
     *
     * @param int $sanitizedProductShowInSearch Product show in search after it has been sanitized.
     * @param int $rawProductShowInSearch The product's show in search.
     */
    $productShowInSearch = Filter::getInstance()->applyFilter(
        'pre_product_show_in_search',
        (int) $sanitizedProductShowInSearch,
        (int) $rawProductShowInSearch
    );
    $product->showInSearch = $productShowInSearch;

    $rawProductFeaturedImage = cms_optimized_image_upload($productdata['featuredImage']);
    $sanitizedProductFeaturedImage = Sanitizer::item($rawProductFeaturedImage);
    /**
     * Filters a product's featured image before the product is created or updated.
     *
     * @param string $sanitizedProductFeaturedImage Product featured image url after it has been sanitized.
     * @param string $rawProductFeaturedImage The product's featured image url.
     */
    $productFeaturedImage = Filter::getInstance()->applyFilter(
        'pre_product_featured_image',
        (string) $sanitizedProductFeaturedImage,
        (string) $rawProductFeaturedImage
    );
    $product->featuredImage = $productFeaturedImage;

    $rawProductStatus = $productdata['status'];
    $sanitizedProductStatus = Sanitizer::item($rawProductStatus);
    /**
     * Filters a product's status before the product is created or updated.
     *
     * @param string $sanitizedProductStatus Product status after it has been sanitized.
     * @param string $rawProductStatus The product's status.
     */
    $productStatus = Filter::getInstance()->applyFilter(
        'pre_product_status',
        (string) $sanitizedProductStatus,
        (string) $rawProductStatus
    );
    $product->status = $productStatus;

    /*
     * Filters whether the product is null.
     *
     * @param bool  $maybe_empty Whether the product should be considered "null".
     * @param array $productdata   Array of product data.
     */
    $maybeNull = !$productTitle && !$productBody;
    if (Filter::getInstance()->applyFilter('cms_insert_empty_product', $maybeNull, $productdata)) {
        return new ProductError(message: esc_html__(string: 'The title and product are null.', domain: 'devflow'));
    }

    if (!$update) {
        if (empty($productdata['published']) || php_like('%0000-00-00 00:00', $productdata['published'])) {
            $productPublished = (new DateTime('now', get_user_timezone()))->getDateTime();
            $productPublishedGmt = (new DateTime('now', 'GMT'))->getDateTime();
            $productCreated = $productPublished;
            $productCreatedGmt = $productPublishedGmt;
        } else {
            $productPublished = (new DateTime(
                str_replace(['AM', 'PM'], '', $productdata['published']),
                get_user_timezone()
            ))->getDateTime();
            $productPublishedGmt = (new DateTime($productdata['publishedGmt'] ?? 'now', 'GMT'))->getDateTime();
            $productCreated = $productPublished;
            $productCreatedGmt = $productPublishedGmt;
        }
    } else {
        $productPublished = (new DateTime(
            str_replace(['AM', 'PM'], '', $productdata['published']),
            get_user_timezone()
        ))->getDateTime();
        $productPublishedGmt = (new DateTime(
            $productdata['publishedGmt'] ?? str_replace(['AM', 'PM'], '', $productdata['published']),
            get_user_timezone()
        ))->getDateTime();
        $productCreated = $productPublished;
        $productCreatedGmt = $productPublishedGmt;
        $productModified = (new DateTime(QubusDateTimeImmutable::now(get_user_timezone())->toDateTimeString()))
                ->getDateTime();
        $productModifiedGmt = (new DateTime(QubusDateTimeImmutable::now('GMT')->toDateTimeString()))->getDateTime();
    }

    $dataArray = [
        'id' => $productId->toNative(),
        'slug' => $productSlug,
        'body' => $productBody,
        'author' => $productAuthor,
        'sku' => $productSku,
        'price' => (string) $productPrice,
        'currency' => $productCurrency,
        'purchaseUrl' => $productPurchaseUrl,
        'showInMenu' => (string) $productShowInMenu,
        'showInSearch' => (string) $productShowInSearch,
        'featuredImage' => $productFeaturedImage,
        'status' => $productStatus,
        'created' => (string) $productCreated,
        'createdGmt' => (string) $productCreatedGmt,
        'published' => (string) $productPublished,
        'publishedGmt' => (string) $productPublishedGmt,
    ];
    $productDataArray = unslash($dataArray);

    // Product custom fields.
    $metaFields = $productdata['product_field'] ?? [];

    /**
     * Filters product data before the record is created or updated.
     *
     * It only includes data in the product table, not any product metadata.
     *
     * @param array    $productDataArray
     *     Values and keys for the user.
     *
     *      @type string $productTitle         The product's title.
     *      @type string $productBody          The product's body.
     *      @type string $productSlug          The product's slug.
     *      @type string $productAuthor        The product's author.
     *      @type string $productSku           The product's sku.
     *      @type string $productPrice         The product's price.
     *      @type string $productCurrency      The product's currency.
     *      @type string $productPurchaseUrl   The product's purchaseUrl.
     *      @type string $productShowInMenu    Whether to show product in menu.
     *      @type string $productShowInSearch  Whether to show product in search.
     *      @type string $productFeaturedImage The product's featured image.
     *      @type string $productStatus        The product's status.
     *      @type string $productCreated       Timestamp of when the product was created.
     *                                         Defaults to Y-m-d H:i:s A.
     *      @type string $productCreatedGmt    Timestamp of when the product was created
     *                                         in GMT. Defaults to Y-m-d H:i:s A.
     *      @type string $productPublished     Timestamp describing the moment when the product
     *                                         was published. Defaults to Y-m-d H:i:s A.
     *      @type string $productPublishedGmt  Timestamp describing the moment when the product
     *                                         was published in GMT. Defaults to Y-m-d H:i:s A.
     *      @type string $productModified      Timestamp of when the product was modified.
     *                                         Defaults to Y-m-d H:i:s A.
     *      @type string $productModifiedGmt   Timestamp of when the product was modified
     *                                         in GMT. Defaults to Y-m-d H:i:s A.
     *
     * @param bool     $update Whether the product is being updated rather than created.
     * @param string|null $id  ID of the product to be updated, or NULL if the product is being created.
     */
    Filter::getInstance()->applyFilter(
        'cms_before_insert_product_data',
        $productDataArray,
        $update,
        $update ? $productBefore->id : $productId,
    );

    $resolver = new NativeCommandHandlerResolver(
        container: ContainerFactory::make(config: config(key: 'commandbus.container'))
    );
    $odin = new Odin(bus: new SynchronousCommandBus($resolver));

    if (!$update) {
        /**
         * Fires immediately before a product is inserted into the product document.
         *
         * @param Product $product Product object.
         */
        Action::getInstance()->doAction('pre_product_insert', $product);

        try {
            $command = new CreateProductCommand([
                'id' => ProductId::fromString($productId->toNative()),
                'title' => new StringLiteral($productTitle),
                'slug' => new StringLiteral($productSlug),
                'body' => new StringLiteral($productBody ?? ''),
                'author' => UserId::fromString($productAuthor),
                'sku' => new StringLiteral($productSku),
                'price' => new Money(new IntegerNumber($productPrice), new Currency(CurrencyCode::$productCurrency())),
                'purchaseUrl' => new StringLiteral($productPurchaseUrl ?? ''),
                'showInMenu' => new IntegerNumber($productShowInMenu),
                'showInSearch' => new IntegerNumber($productShowInSearch),
                'featuredImage' => new StringLiteral($productFeaturedImage ?? ''),
                'meta' => new ArrayLiteral($metaFields),
                'status' => new StringLiteral($productStatus),
                'created' => $productCreated,
                'createdGmt' => $productCreatedGmt,
                'published' => $productPublished,
                'publishedGmt' => $productPublishedGmt,
            ]);

            $odin->execute($command);
        } catch (PDOException $ex) {
            FileLoggerFactory::getLogger()->error(
                sprintf(
                    'SQLSTATE[%s]: %s',
                    $ex->getCode(),
                    $ex->getMessage()
                ),
                [
                    'Product Function' => 'cms_insert_product'
                ]
            );

            return new ProductError(message: esc_html__(
                string: 'Could not insert product into the product table.',
                domain: 'devflow'
            ));
        }

    } else {
        /**
         * Fires immediately before existing product is updated in the product document.
         *
         * @param string  $productId Product id.
         * @param product $product   Product object.
         */
        Action::getInstance()->doAction('pre_product_update', $productId, $product);

        try {
            $command = new UpdateProductCommand([
                'id' => ProductId::fromString($productId->toNative()),
                'title' => new StringLiteral($productTitle),
                'slug' => new StringLiteral($productSlug),
                'body' => new StringLiteral($productBody),
                'author' => UserId::fromString($productAuthor),
                'sku' => new StringLiteral($productSku),
                'price' => new Money(new IntegerNumber($productPrice), new Currency(CurrencyCode::$productCurrency())),
                'purchaseUrl' => new StringLiteral($productPurchaseUrl ?? ''),
                'showInMenu' => new IntegerNumber($productShowInMenu),
                'showInSearch' => new IntegerNumber($productShowInSearch),
                'featuredImage' => new StringLiteral($productFeaturedImage ?? ''),
                'meta' => new ArrayLiteral($metaFields),
                'status' => new StringLiteral($productStatus),
                'published' => $productPublished,
                'publishedGmt' => $productPublishedGmt,
                'modified' => $productModified,
                'modifiedGmt' => $productModifiedGmt,
            ]);

            $odin->execute($command);
        } catch (PDOException $ex) {
            FileLoggerFactory::getLogger()->error(
                sprintf(
                    'SQLSTATE[%s]: %s',
                    $ex->getCode(),
                    $ex->getMessage()
                ),
                [
                    'Product Function' => 'cms_insert_product'
                ]
            );

            return new ProductError(message: esc_html__(
                string: 'Could not update product within the product table.',
                domain: 'devflow'
            ));
        }
    }

    if (!empty($metaFields)) {
        foreach ($metaFields as $key => $value) {
            update_productmeta($productId->toNative(), $key, $value);
        }
    }

    /** @var Product $product */
    $product = get_product_by_id($productId->toNative());

    ProductCachePsr16::clean($product);

    if ($update) {
        /**
         * Action hook triggered after existing product has been updated.
         *
         * @param string $productId Product id.
         * @param array  $product   Product object.
         */
        Action::getInstance()->doAction('update_product', $productId, $product);
        /** @var Product $productAfter */
        $productAfter = get_product_by_id($productId->toNative());
        /**
         * Action hook triggered after existing product has been updated.
         *
         * @param string $productId      Product id.
         * @param object $productAfter   Product object following the update.
         * @param object $productBefore  Product object before the update.
         */
        Action::getInstance()->doAction('product_updated', $productId->toNative(), $productAfter, $productBefore);
    } else {
        /**
         * Action hook triggered after product is created.
         *
         * @param array $product Product object.
         */
        Action::getInstance()->doAction('create_product', $product);
    }

    /**
     * Action hook triggered after product has been saved.
     *
     * The dynamic portion of this hook, `$productSku`, is the product's
     * sku.
     *
     * @param string $productId The product's id.
     * @param array $product    Product object.
     * @param bool  $update     Whether this is an existing product or a new product.
     */
    Action::getInstance()->doAction("save_product_{$productSku}", $productId->toNative(), $product, $update);

    /**
     * Action hook triggered after product has been saved.
     *
     * The dynamic portions of this hook, `$productSku` and `$productStatus`,
     * are the product's sku and status.
     *
     * @param string $productId The product's id.
     * @param array  $product   Product object.
     * @param bool   $update    Whether this is existing product or new product.
     */
    Action::getInstance()->doAction(
        "save_product_{$productSku}_{$productStatus}",
        $productId->toNative(),
        $product,
        $update
    );

    /**
     * Action hook triggered after product has been saved.
     *
     * @param string $productId The product's id.
     * @param object $product   Product object.
     * @param bool   $update    Whether this is existing product or new product.
     */
    Action::getInstance()->doAction('cms_after_insert_product_data', $productId->toNative(), $product, $update);

    return $productId->toNative();
}

/**
 * Update a product in the product document.
 *
 * See {@see cms_insert_product()} For what fields can be set in $productdata.
 *
 * @file App/Shared/Helpers/product.php
 * @param array|ServerRequestInterface|Product $productdata An array of product data or a product object.
 * @return string|Error The updated product's id or return Error if product could not be updated.
 * @throws CommandCouldNotBeHandledException
 * @throws CommandPropertyNotFoundException
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 * @throws TypeException
 * @throws UnresolvableCommandHandlerException
 * @throws UnresolvableQueryHandlerException
 */
function cms_update_product(array|ServerRequestInterface|Product $productdata): string|Error
{
    if ($productdata instanceof ServerRequestInterface) {
        $productdata = $productdata->getParsedBody();
    } elseif ($productdata instanceof Product) {
        $productdata = $productdata->toArray();
    }

    // First, get all the original fields.
    /** @var Product $product */
    $product = get_product_by_id($productdata['id']);

    if (is_null__($product->id) || '' === $product->id) {
        return new ProductError(message: esc_html__(string: 'Invalid product id.', domain: 'devflow'));
    }

    // Merge old and new fields with new fields overwriting old ones.
    $productdata = array_merge($product->toArray(), $productdata);

    return cms_insert_product($productdata);
}

/**
 * Deletes product from the product document.
 *
 * @file App/Shared/Helpers/product.php
 * @param string $productId The id of the product to delete.
 * @return bool|Product Product on success or false on failure.
 * @throws CommandCouldNotBeHandledException
 * @throws CommandPropertyNotFoundException
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 * @throws TypeException
 * @throws UnresolvableCommandHandlerException
 */
function cms_delete_product(string $productId): Product|bool
{
    /** @var Product $product */
    $product = get_product_by_id($productId);

    if (is_false__($product)) {
        return false;
    }

    $resolver = new NativeCommandHandlerResolver(
        container: ContainerFactory::make(config: config(key: 'commandbus.container'))
    );
    $odin = new Odin(bus: new SynchronousCommandBus($resolver));

    /**
     * Action hook fires before a product is deleted.
     *
     * @param string $productId Product id.
     */
    Action::getInstance()->doAction('before_delete_product', $productId);

    $productMetaKeys = get_productmeta($productId);
    if ($productMetaKeys) {
        foreach ($productMetaKeys as $metaKey => $metaValue) {
            delete_productmeta($productId, $metaKey, $metaValue);
        }
    }

    /**
     * Action hook fires immediately before a product is deleted from the
     * product document.
     *
     * @param string $productId Product ID.
     */
    Action::getInstance()->doAction('delete_product', $productId);

    try {
        $command = new DeleteProductCommand([
            'productId' => ProductId::fromString($product->id),
        ]);

        $odin->execute($command);
    } catch (PDOException $ex) {
        FileLoggerFactory::getLogger()->error(
            sprintf(
                'SQLSTATE[%s]: %s',
                $ex->getCode(),
                $ex->getMessage()
            ),
            [
                'Product Function' => 'cms_delete_product'
            ]
        );
    }

    /**
     * Action hook fires immediately after a product is deleted from the product document.
     *
     * @param string $productId Product id.
     */
    Action::getInstance()->doAction('deleted_product', $productId);

    /**
     * Action hook fires after a product is deleted.
     *
     * @param string $productId Product id.
     */
    Action::getInstance()->doAction('after_delete_product', $productId);

    return $product;
}

/**
 * Retrieves an array of css class names.
 *
 * @file App/Shared/Helpers/product.php
 * @param string $productId Product id of current product.
 * @param string|array $class One or more css class names to add to element list.
 * @return array An array of css class names.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_product_class(string $productId, string|array $class = ''): array
{
    /** @var Product $product */
    $product = get_product_by_id($productId);

    $classes = [];

    if ($class) {
        if (!is_array($class)) {
            $class = preg_split('#\s+#', $class);
        }
        $classes = array_map('\Qubus\Security\Helpers\esc_attr', $class);
    } else {
        $class = [];
    }

    if (!$product) {
        return $classes;
    }

    $classes[] = 'product-' . $product->id;
    $classes[] = 'producttype-' . $product->productType;

    $classes = array_map('\Qubus\Security\Helpers\esc_attr', $classes);
    /**
     * Filters the list of CSS class names for the current product.
     *
     * @param array $classes An array of css class names.
     * @param array $class   An array of additional css class names.
     * @param string $productId Product id of the current product.
     */
    $classes = Filter::getInstance()->applyFilter('product_class', $classes, $class, $product->id);

    return array_unique($classes);
}

/**
 * Retrieves and displays product meta value.
 *
 * Uses `the_product_meta` filter.
 *
 * @file App/Shared/Helpers/product.php
 * @param string|Product|ProductId $product Product object or id.
 * @param string $key Product meta key.
 * @return string Product meta value.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function the_product_meta(string|Product|ProductId $product, string $key): string
{
    if ($product instanceof Product) {
        $product = $product->id;
    }

    if ($product instanceof ProductId) {
        $product = $product->toNative();
    }

    $theMeta = get_productmeta(productId: $product, key: $key, single: true);
    /**
     * Filters product meta.
     *
     * @file App/Shared/Helpers/product.php
     * @param mixed  $theMeta Product meta value.
     * @param string $key     Product meta key.
     */
    return Filter::getInstance()->applyFilter('the_product_meta', $theMeta, $key);
}

/**
 * Currency dropdown options.
 *
 * @param string|null $active Currency selected.
 * @return void
 */
function currency_option(?string $active = null): void
{
    foreach (config(key: 'currency') as $code => $currency) {
        echo '<option value="' . $code . '"' . selected($code, $active, false) . '>' . $code . '</option>' . "\r\n";
    }
}

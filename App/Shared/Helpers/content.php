<?php

declare(strict_types=1);

namespace App\Shared\Helpers;

use App\Domain\Content\Model\Content;
use App\Domain\Content\Command\CreateContentCommand;
use App\Domain\Content\Command\DeleteContentCommand;
use App\Domain\Content\Command\RemoveContentParentCommand;
use App\Domain\Content\Command\UpdateContentCommand;
use App\Domain\Content\ContentError;
use App\Domain\Content\Query\FindContentByTypeAndIdQuery;
use App\Domain\Content\Query\FindContentQuery;
use App\Domain\Content\ValueObject\ContentId;
use App\Domain\ContentType\ContentType;
use App\Domain\User\ValueObject\UserId;
use App\Infrastructure\Persistence\Cache\ContentCachePsr16;
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
use DateInvalidTimeZoneException;
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
 * Retrieve all the content regardless of status.
 *
 * @return array
 * @throws ReflectionException
 * @throws UnresolvableQueryHandlerException
 */
function get_content(): array
{
    $resolver = new NativeQueryHandlerResolver(container: ContainerFactory::make(config: config('querybus.aliases')));
    $enquirer = new Enquire(bus: new SynchronousQueryBus($resolver));

    $query = new FindContentQuery();

    return $enquirer->execute($query);
}

/**
 * Retrieve all content or content based on filters.
 *
 * @file App/Shared/Helpers/content.php
 * @param string|null $contentTypeSlug Content type slug.
 * @param int $limit Number of content to show.
 * @param int|null $offset The offset of the first row to be returned.
 * @param string $status Returned unescaped content based on status (all, draft, published, pending, archived)
 * @return array Array of published content or content by particular content type.
 * @throws CommandPropertyNotFoundException
 * @throws ReflectionException
 * @throws UnresolvableQueryHandlerException
 */
function get_all_content_with_filters(
    ?string $contentTypeSlug = null,
    int $limit = 0,
    int $offset = null,
    string $status = 'all'
): array {
    $resolver = new NativeQueryHandlerResolver(container: ContainerFactory::make(config: config('querybus.aliases')));
    $enquirer = new Enquire(bus: new SynchronousQueryBus($resolver));

    $query = new FindContentQuery([
        'contentTypeSlug' => $contentTypeSlug,
        'limit' => $limit,
        'offset' => $offset,
        'status' => $status,
    ]);

    return $enquirer->execute($query);
}

/**
 * Retrieves content by content type slug and content id.
 *
 * @file App/Shared/Helpers/content.php
 * @param string $contentTypeSlug
 * @param string $contentId
 * @return false|object
 * @throws CommandPropertyNotFoundException
 * @throws ReflectionException
 * @throws TypeException
 * @throws UnresolvableQueryHandlerException
 */
function get_content_by_type_and_id(string $contentTypeSlug, string $contentId): false|object
{
    $resolver = new NativeQueryHandlerResolver(
        container: ContainerFactory::make(config: config(key: 'querybus.aliases'))
    );
    $enquirer = new Enquire(bus: new SynchronousQueryBus($resolver));

    $query = new FindContentByTypeAndIdQuery([
            'contentType' => new StringLiteral($contentTypeSlug),
            'contentId' => ContentId::fromString($contentId),
    ]);

    $results = $enquirer->execute($query);

    if (is_null__($results) || is_false__($results)) {
        return false;
    }

    return Content::hydrate($results);
}

/**
 * Retrieve content by a given field from the content table.
 *
 * @file App/Shared/Helpers/content.php
 * @param string $field The field to retrieve the content with
 *                      (id = content_id, type = content_content_type, slug = content_slug).
 * @param string $value A value for $field (content_id, content_content_type, content_slug).
 * @return false|object
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_content_by(string $field, string $value): false|object
{
    $contentdata = (new Content(dfdb()))->findBy($field, $value);

    if (is_false__($contentdata)) {
        return false;
    }

    return $contentdata;
}

/**
 * Retrieve content by the content id.
 *
 * @file App/Shared/Helpers/content.php
 * @param string $contentId
 * @return false|object
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_content_by_id(string $contentId): object|false
{
    return get_content_by('id', $contentId);
}

/**
 * A function which retrieves content datetime.
 *
 * Purpose of this function is for the `content_datetime`
 * filter.
 *
 * @file App/Shared/Helpers/content.php
 * @param string|null $content
 * @return string Content datetime.
 * @throws ReflectionException
 * @throws Exception
 */
function get_content_datetime(?string $content = null): string
{
    $datetime = concat_ws(
        get_content_date('published', $content),
        get_content_time('published', $content),
        ' ',
    );
    /**
     * Filters the content's datetime.
     *
     * @file App/Shared/Helpers/content.php
     * @param string $datetime  The content's datetime.
     * @param string $contentId Content id or content object.
     */
    return Filter::getInstance()->applyFilter('content_datetime', $datetime, $content);
}

/**
 * A function which retrieves content modified datetime.
 *
 * Purpose of this function is for the `content_modified`
 * filter.
 *
 * @file App/Shared/Helpers/content.php
 * @param string $contentId Content id.
 * @return string Content modified datetime or '' on failure.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_content_modified(string $contentId): string
{
    /** @var Content $content */
    $content = get_content_by_id($contentId);

    if (is_false__($content)) {
        return '';
    }

    $format = get_user_datetime_format();

    $modified = get_user_datetime($content->modifiedGmt, $format);

    /**
     * Filters the content date.
     *
     * @file App/Shared/Helpers/content.php
     * @param string $modified The content's modified datetime.
     * @param string $format   Format to return datetime string.
     * @param string $contentId Content id or content object.
     */
    return Filter::getInstance()->applyFilter('content_modified', $modified, $format, $content);
}

/**
 * A function which retrieves a content body.
 *
 * Purpose of this function is for the `content_body`
 * filter.
 *
 * @file App/Shared/Helpers/content.php
 * @param string $contentId Content id.
 * @return string Content body or '' on failure.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_content_body(string $contentId): string
{
    /** @var Content $content */
    $content = get_content_by_id($contentId);

    if (is_false__($content)) {
        return '';
    }

    $body = $content->body;
    /**
     * Filters the content date.
     *
     * @file App/Shared/Helpers/content.php
     * @param string $body    The content's body.
     * @param string $content Content object.
     */
    return Filter::getInstance()->applyFilter('content_body', $body, $content);
}

/**
 * A function which retrieves a content content_type name.
 *
 * Purpose of this function is for the `content_content_type_name`
 * filter.
 *
 * @file App/Shared/Helpers/content.php
 * @param string $contentId Content id.
 * @return string|false Content type name or '' on failure.
 * @throws CommandPropertyNotFoundException
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 * @throws TypeException
 * @throws UnresolvableQueryHandlerException
 */
function get_content_contenttype_name(string $contentId): false|string
{
    /** @var Content $content */
    $content = get_content_by_id($contentId);

    if (is_false__($content)) {
        return '';
    }

    $contentType = get_content_type_by('slug', $content->type);
    $contentTypeName = $contentType->title;
    /**
     * Filters the content content_type name.
     *
     * @file App/Shared/Helpers/content.php
     * @param string $contentTypeName The content's content_type name.
     * @param string $content         Content object.
     */
    return Filter::getInstance()->applyFilter('content_contenttype_name', $contentTypeName, $content);
}

/**
 * A function which retrieves a content content_type link.
 *
 * Purpose of this function is for the `content_content_type_link`
 * filter.
 *
 * @file App/Shared/Helpers/content.php
 * @param string $contentId Content id.
 * @return string Content Type link.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_content_contenttype_link(string $contentId): string
{
    $link = site_url(get_content_contenttype($contentId) . '/');
    /**
     * Filters the content content_type link.
     *
     * @file App/Shared/Helpers/content.php
     * @param string $link      The content's content_type link.
     * @param string $contentId Content id.
     */
    return Filter::getInstance()->applyFilter('content_contenttype_link', $link, $contentId);
}

/**
 * A function which retrieves a content title.
 *
 * Purpose of this function is for the `content_title`
 * filter.
 *
 * @file App/Shared/Helpers/content.php
 * @param string $contentId Content id.
 * @return string Content title or '' on failure.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_content_title(string $contentId): string
{
    /** @var Content $content */
    $content = get_content_by_id($contentId);

    if (is_false__($content)) {
        return '';
    }

    $title = $content->title;
    /**
     * Filters the content title.
     *
     * @file App/Shared/Helpers/content.php
     * @param string $title The content's title.
     * @param string $content  Content object.
     */
    return Filter::getInstance()->applyFilter('content_title', $title, $content);
}

/**
 * A function which retrieves a content slug.
 *
 * Purpose of this function is for the `content_slug`
 * filter.
 *
 * @file App/Shared/Helpers/content.php
 * @param string $contentId Content id.
 * @return string Content slug or ''.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_content_slug(string $contentId): string
{
    /** @var Content $content */
    $content = get_content_by_id($contentId);

    if (is_false__($content)) {
        return '';
    }

    $slug = $content->slug;
    /**
     * Filters the content's slug.
     *
     * @file App/Shared/Helpers/content.php
     * @param string $slug The content's slug.
     * @param string $content   Content object.
     */
    return Filter::getInstance()->applyFilter('content_slug', $slug, $content);
}

/**
 * A function which retrieves a content's relative url.
 *
 * Purpose of this function is for the `{$contenttype}_relative_url`
 * filter.
 *
 * @file App/Shared/Helpers/content.php
 * @param string $contentId Content id.
 * @return string Content relative url or '' on failure.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_content_relative_url(string $contentId): string
{
    /** @var Content $content */
    $content = get_content_by_id($contentId);

    if (is_false__($content)) {
        return '';
    }

    $relativeUrl = $content->relativeUrl;
    /**
     * Filters the content's relative_url.
     *
     * @file App/Shared/Helpers/content.php
     * @param string $relativeUrl The content's relative url.
     * @param string $content   The content object.
     */
    return Filter::getInstance()->applyFilter(
        "{$content->type}_relative_url",
        $relativeUrl,
        $content
    );
}

/**
 * A function which retrieves a content's permalink.
 *
 * Purpose of this function is for the `{$contenttype}_link`
 * filter.
 *
 * @file App/Shared/Helpers/content.php
 * @param string $contentId Content id.
 * @return string Content permalink or '' on failure.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_permalink(string $contentId): string
{
    /** @var Content $content */
    $content = get_content_by_id($contentId);

    if (is_false__($content)) {
        return '';
    }

    $link = home_url(get_content_relative_url($contentId));
    /**
     * Filters the content's link based on its content_type.
     *
     * @file App/Shared/Helpers/content.php
     * @param string $link The content's link.
     * @param object $content Content object.
     */
    return Filter::getInstance()->applyFilter("{$content->type}_link", $link, $content);
}

/**
 * Wrapper function for `get_all_content_with_filters`.
 *
 * @file App/Shared/Helpers/content.php
 * @param string|null $contentType The content type.
 * @param int $limit Number of content to show.
 * @param int|null $offset The offset of the first row to be returned.
 * @param string $status Should it retrieve all statuses, published, draft, etc.?
 * @return array Content.
 * @throws CommandPropertyNotFoundException
 * @throws ReflectionException
 * @throws UnresolvableQueryHandlerException
 */
function the_content(?string $contentType = null, int $limit = 0, int $offset = null, string $status = 'all'): array
{
    return get_all_content_with_filters($contentType, $limit, $offset, $status);
}

/**
 * Adds label to content's status.
 *
 * @file App/Shared/Helpers/content.php
 * @param string $status
 * @return string Content status label.
 */
function content_status_label(string $status): string
{
    $label = [
        'published' => 'label-success',
        'draft' => 'label-warning',
        'archived' => 'label-danger'
    ];

    return $label[$status];
}

/**
 * Retrieve content meta field for a content.
 *
 * @file App/Shared/Helpers/content.php
 * @param string $contentId Content ID.
 * @param string $key Optional. The meta key to retrieve.
 * @param bool $single Optional. Whether to return a single value. Default false.
 * @return mixed Will be an array if $single is false. Will be value of metadata
 *               field if $single is true.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_contentmeta(string $contentId, string $key = '', bool $single = false): mixed
{
    return MetaData::factory(dfdb()->prefix . 'contentmeta')->read('content', $contentId, $key, $single);
}

/**
 * Get content meta data by meta ID.
 *
 * @file App/Shared/Helpers/content.php
 * @param string $mid
 * @return array|bool
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_contentmeta_by_mid(string $mid): bool|array
{
    return MetaData::factory(dfdb()->prefix . 'contentmeta')->readByMid('content', $mid);
}

/**
 * Update content meta field based on content ID.
 *
 * Use the $prevValue parameter to differentiate between meta fields with the
 * same key and content ID.
 *
 * If the meta field for the content does not exist, it will be added.
 *
 * @file App/Shared/Helpers/content.php
 * @param string $contentId Content ID.
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
function update_contentmeta(
    string $contentId,
    string $metaKey,
    mixed $metaValue,
    mixed $prevValue = ''
): bool|string {
    return MetaData::factory(dfdb()->prefix . 'contentmeta')
            ->update('content', $contentId, $metaKey, $metaValue, $prevValue);
}

/**
 * Update content meta data by meta ID.
 *
 * @file App/Shared/Helpers/content.php
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
function update_contentmeta_by_mid(string $mid, string $metaKey, string $metaValue): bool
{
    $_metaKey = unslash($metaKey);
    $_metaValue = unslash($metaValue);

    return MetaData::factory(dfdb()->prefix . 'contentmeta')->updateByMid('content', $mid, $_metaKey, $_metaValue);
}

/**
 * Add meta data field to a content.
 *
 * @file App/Shared/Helpers/content.php
 * @param string $contentId Content ID.
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
function add_contentmeta(string $contentId, string $metaKey, mixed $metaValue, bool $unique = false): false|string
{
    return MetaData::factory(dfdb()->prefix . 'contentmeta')
            ->create('content', $contentId, $metaKey, $metaValue, $unique);
}

/**
 * Remove metadata matching criteria from a content.
 *
 * You can match based on the key, or key and value. Removing based on key and
 * value, will keep from removing duplicate metadata with the same key. It also
 * allows removing all metadata matching key, if needed.
 *
 * @file App/Shared/Helpers/content.php
 * @param string $contentId Content ID.
 * @param string $metaKey Metadata name.
 * @param mixed $metaValue Optional. Metadata value. Must be serializable if
 *                         non-scalar. Default empty.
 * @return bool True on success, false on failure.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function delete_contentmeta(string $contentId, string $metaKey, mixed $metaValue = ''): bool
{
    return MetaData::factory(dfdb()->prefix . 'contentmeta')->delete('content', $contentId, $metaKey, $metaValue);
}

/**
 * Delete content meta data by meta ID.
 *
 * @file App/Shared/Helpers/content.php
 * @param string $mid
 * @return bool
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function delete_contentmeta_by_mid(string $mid): bool
{
    return MetaData::factory(dfdb()->prefix . 'contentmeta')->deleteByMid('content', $mid);
}

/**
 * Retrieve content meta fields, based on content ID.
 *
 * The content meta fields are retrieved from the cache where possible,
 * so the function is optimized to be called more than once.
 *
 * @file App/Shared/Helpers/content.php
 * @param string $contentId The content's id.
 * @return mixed Content meta for the given content.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_content_custom(string $contentId): mixed
{
    return get_contentmeta($contentId);
}

/**
 * Retrieve meta field names for a content.
 *
 * If there are no meta fields, then nothing (null) will be returned.
 *
 * @file App/Shared/Helpers/content.php
 * @param string $contentId The content's id.
 * @return array Array of the keys, if retrieved.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_content_custom_keys(string $contentId): array
{
    $custom = get_content_custom($contentId);
    if (!is_array($custom)) {
        return [];
    }
    if ($keys = array_keys($custom)) {
        return $keys;
    }

    return [];
}

/**
 * Retrieve values for a custom content field.
 *
 * The parameters must not be considered optional. All the content meta fields
 * will be retrieved and only the meta field key values returned.
 *
 * @file App/Shared/Helpers/content.php
 * @param string $contentId The content's id.
 * @param string $key Meta field key.
 * @return array Meta field values or [].
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_content_custom_values(string $contentId, string $key): array
{
    $custom = get_content_custom($contentId);
    return $custom[$key] ?? [];
}

/**
 * A function which retrieves a content author id.
 *
 * Purpose of this function is for the `content_author_id`
 * filter.
 *
 * @file App/Shared/Helpers/content.php
 * @param string $contentId Content id.
 * @return false|string Content author id or false on failure.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_content_author_id(string $contentId): false|string
{
    /** @var Content $content */
    $content = get_content_by_id($contentId);

    if (is_false__($content)) {
        return '';
    }

    $authorId = $content->author;
    /**
     * Filters the content author id.
     *
     * @file App/Shared/Helpers/content.php
     * @param string $authorId The content's author id.
     * @param object $content Content object.
     */
    return Filter::getInstance()->applyFilter('content_author_id', $authorId, $content);
}

/**
 * A function which retrieves a content author.
 *
 * Purpose of this function is for the `content_author`
 * filter.
 *
 * @file App/Shared/Helpers/content.php
 * @param string $contentId Optional Content id or content object.
 * @param bool $reverse If first name should appear first or not. Default is false.
 * @return string|false Content author or false on failure.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_content_author(string $contentId, bool $reverse = false): false|string
{
    /** @var Content $content */
    $content = get_content_by_id($contentId);

    if (is_false__($content)) {
        return false;
    }

    $author = get_name($content->author, $reverse);
    /**
     * Filters the content author.
     *
     * @file App/Shared/Helpers/content.php
     * @param string $author The content's author.
     * @param object   $content Content object.
     */
    return Filter::getInstance()->applyFilter('content_author', $author, $content);
}

/**
 * A function which retrieves a content status.
 *
 * Purpose of this function is for the `content_status`
 * filter.
 *
 * @file App/Shared/Helpers/content.php
 * @param string $contentId Content id.
 * @return string|false Content status or false on failure.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_content_status(string $contentId): false|string
{
    /** @var Content $content */
    $content = get_content_by_id($contentId);

    if (is_false__($content)) {
        return false;
    }

    $status = $content->status;
    /**
     * Filters the content status.
     *
     * @file App/Shared/Helpers/content.php
     * @param string $status The content's status.
     * @param Content   $content Content object.
     */
    return Filter::getInstance()->applyFilter('content_status', $status, $content);
}

/**
 * A function which retrieves content date.
 *
 * Uses `call_user_func_array()` function to return appropriate content date function.
 * Dynamic part is the variable $type, which calls the date function you need.
 *
 * @file App/Shared/Helpers/content.php
 * @param string $type Type of date to return: created, published, modified. Default: published.
 * @param string $contentId Content id.
 * @return string Content date.
 */
function get_content_date(string $type = 'published', string $contentId = ''): string
{
    return call_user_func_array("the_{$type}_date", ['Y-m-d',&$contentId]);
}

/**
 * A function which retrieves content time.
 *
 * Uses `call_user_func_array()` function to return appropriate content time function.
 * Dynamic part is the variable $type, which calls the date function you need.
 *
 * @file App/Shared/Helpers/content.php
 * @param string $type Type of date to return: created, published, modified. Default: published.
 * @param string $contentId Content id.
 * @return string Content time.
 */
function get_content_time(string $type = 'published', string $contentId = ''): string
{
    return call_user_func_array("the_{$type}_time", ['h:i A',&$contentId]);
}

/**
 * Retrieves content created date.
 *
 * @file App/Shared/Helpers/content.php
 * @param string $contentId Content id.
 * @param string $format Format to use for retrieving the date the content was created.
 *                       Accepts 'G', 'U', or php date format value specified
 *                       in 'date_format' option. Default 'U'.
 * @param bool $gmt Whether to use GMT. Default false.
 * @param bool $translate Whether the returned string should be translated. Default false.
 * @return string Formatted content created date string or Unix timestamp
 *                if $format is 'U' or 'G'. '' on failure.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_content_created_date(
    string $contentId,
    string $format = 'U',
    bool $gmt = false,
    bool $translate = false
): string {
    /** @var Content $content */
    $content = get_content_by_id($contentId);

    if (is_false__($content)) {
        return '';
    }

    $date = new DateTime();

    if ($gmt) {
        $theDate = get_user_datetime($content->createdGmt);
    } else {
        $theDate = $content->created;
    }

    $theDate = $date->db2Date($format, $theDate, $translate);

    if (is_false__($theDate)) {
        return '';
    }

    /**
     * Filters the content created date.
     *
     * @file App/Shared/Helpers/content.php
     * @param string $theDate The content's formatted date.
     * @param bool   $format Format to use for retrieving the date the content was written.
     *                       Accepts 'G', 'U', or php date format. Default 'U'.
     * @param bool   $gmt    Whether to retrieve the GMT date. Default false.
     */
    return Filter::getInstance()->applyFilter('get_content_created_date', $theDate, $format, $gmt);
}

/**
 * Retrieves content created date.
 *
 * @file App/Shared/Helpers/content.php
 * @param string $contentId Content id.
 * @param string $format Format to use for retrieving the date the content was created.
 *                       Accepts 'G', 'U', or php date format value specified
 *                       in 'date_format' option. Default empty.
 * @return string Formatted content created date string or Unix timestamp
 *                if $format is 'U' or 'G'. '' on failure.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function the_created_date(string $contentId, string $format = ''): string
{
    /** @var Content $content */
    $content = get_content_by_id($contentId);

    if (is_false__($content)) {
        return '';
    }

    if ('' === $format) {
        $theDate = get_content_created_date(
            $contentId,
            get_user_date_format(),
            true,
            true
        );
    } else {
        $theDate = get_content_created_date($contentId, $format, true, true);
    }

    /**
     * Filters the date the content was written.
     *
     * @file App/Shared/Helpers/content.php
     * @param string    $theDate The formatted date.
     * @param string    $format  Format to use for retrieving the date the content was written.
     *                           Accepts 'G', 'U', or php date format value specified
     *                           in 'date_format' option. Default empty.
     * @param Content  $content  Content object.
     */
    return Filter::getInstance()->applyFilter('content_created_date', $theDate, $format, $content);
}

/**
 * A function which retrieves content created time.
 *
 * Purpose of this function is for the `content_created_time`
 * filter.
 *
 * @file App/Shared/Helpers/content.php
 * @param string $contentId Content id.
 * @param string $format Format to use for retrieving the time the content was created.
 *                        Accepts 'G', 'U', or php date format value specified
 *                        in 'date_format' option. Default 'U'.
 * @param bool $gmt Whether to use GMT. Default false.
 * @param bool $translate Whether the returned string should be translated. Default false.
 * @return string Formatted content created time string or Unix timestamp
 *                          if $format is 'U' or 'G'. '' on failure.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_content_created_time(
    string $contentId,
    string $format = 'U',
    bool $gmt = false,
    bool $translate = false
): string {
    /** @var Content $content */
    $content = get_content_by_id($contentId);

    if (is_false__($content)) {
        return '';
    }

    $date = new DateTime();

    if ($gmt) {
        $theTime = get_user_datetime($content->createdGmt);
    } else {
        $theTime = $content->created;
    }

    $theTime = $date->db2Date($format, $theTime, $translate);

    if (is_false__($theTime)) {
        return '';
    }

    /**
     * Filters the content created time.
     *
     * @file App/Shared/Helpers/content.php
     * @param string $theTime The content's formatted time.
     * @param bool   $format   Format to use for retrieving the time the content was written.
     *                         Accepts 'G', 'U', or php date format. Default 'U'.
     * @param bool   $gmt      Whether to retrieve the GMT time. Default false.
     */
    return Filter::getInstance()->applyFilter('get_content_created_time', $theTime, $format, $gmt);
}

/**
 * Retrieves content created time.
 *
 * @file App/Shared/Helpers/content.php
 * @param string $contentId Content id.
 * @param string $format Format to use for retrieving the time the content was written.
 *                       Accepts 'G', 'U', or php date format value specified
 *                       in 'time_format' option. Default empty.
 * @return string Formatted content created time string or Unix timestamp
 *                if $format is 'U' or 'G'. '' on failure.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function the_created_time(string $contentId, string $format = ''): string
{
    /** @var Content $content */
    $content = get_content_by_id($contentId);

    if (is_false__($content)) {
        return '';
    }

    if ('' === $format) {
        $theTime = get_content_created_time(
            $contentId,
            get_user_time_format(),
            true,
            true
        );
    } else {
        $theTime = get_content_created_time($contentId, $format, true, true);
    }

    if (is_false__($theTime)) {
        return '';
    }

    /**
     * Filters the time the content was written.
     *
     * @file App/Shared/Helpers/content.php
     * @param string    $theTime The formatted time.
     * @param string    $format  Format to use for retrieving the time the content was written.
     *                           Accepts 'G', 'U', or php date format value specified
     *                           in 'time_format' option. Default empty.
     * @param object    $content Content object.
     */
    return Filter::getInstance()->applyFilter('content_created_time', $theTime, $format, $content);
}

/**
 * A function which retrieves content published date.
 *
 * @file App/Shared/Helpers/content.php
 * @param string $contentId Content id.
 * @param string $format Format to use for retrieving the date the content was published.
 *                        Accepts 'G', 'U', or php date format value specified
 *                        in 'date_format' option. Default 'U'.
 * @param bool $gmt Whether to use GMT. Default false.
 * @param bool $translate Whether the returned string should be translated. Default false.
 * @return string Formatted content published date string or Unix timestamp
 *                if $format is 'U' or 'G'. '' on failure.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_content_published_date(
    string $contentId,
    string $format = 'U',
    bool $gmt = false,
    bool $translate = false
): string {
    /** @var Content $content */
    $content = get_content_by_id($contentId);

    if (is_false__($content)) {
        return '';
    }

    $date = new DateTime();

    if ($gmt) {
        $theDate = get_user_datetime($content->publishedGmt);
    } else {
        $theDate = $content->published;
    }

    $theDate = $date->db2Date($format, $theDate, $translate);

    if (is_false__($theDate)) {
        return '';
    }

    /**
     * Filters the content published date.
     *
     * @file App/Shared/Helpers/content.php
     * @param string $theDate The content's formatted date.
     * @param bool $format Format to use for retrieving the date the content was published.
     *                     Accepts 'G', 'U', or php date format. Default 'U'.
     * @param bool   $gmt  Whether to retrieve the GMT date. Default false.
     */
    return Filter::getInstance()->applyFilter('get_content_published_date', $theDate, $format, $gmt);
}

/**
 * Retrieves content published date.
 *
 * @file App/Shared/Helpers/content.php
 * @param string $contentId Content id.
 * @param string $format Format to use for retrieving the date the content was published.
 *                       Accepts 'G', 'U', or php date format value specified
 *                       in 'date_format' option. Default empty.
 * @return string Formatted content published date string or Unix timestamp
 *                if $format is 'U' or 'G'. '' on failure.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function the_published_date(string $contentId, string $format = ''): string
{
    /** @var Content $content */
    $content = get_content_by_id($contentId);

    if (is_false__($content)) {
        return '';
    }

    if ('' === $format) {
        $theDate = get_content_published_date(
            $contentId,
            get_user_date_format(),
            true,
            true
        );
    } else {
        $theDate = get_content_published_date($contentId, $format, true, true);
    }

    if (is_false__($theDate)) {
        return '';
    }

    /**
     * Filters the time the content was written.
     *
     * @file App/Shared/Helpers/content.php
     * @param string    $theDate The formatted date.
     * @param string    $format   Format to use for retrieving the date the content was published.
     *                            Accepts 'G', 'U', or php date format value specified
     *                            in 'date_format' option. Default empty.
     * @param object    $content  Content object.
     */
    return Filter::getInstance()->applyFilter('content_published_date', $theDate, $format, $content);
}

/**
 * A function which retrieves content published time.
 *
 * @file App/Shared/Helpers/content.php
 * @param string $contentId Content id.
 * @param string $format Format to use for retrieving the time the content was published.
 *                        Accepts 'G', 'U', or php date format value specified
 *                        in 'date_format' option. Default 'U'.
 * @param bool $gmt Whether to use GMT. Default false.
 * @param bool $translate Whether the returned string should be translated. Default false.
 * @return string Formatted content published time string or Unix timestamp
 *                if $format is 'U' or 'G'. '' on failure.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_content_published_time(
    string $contentId,
    string $format = 'U',
    bool $gmt = false,
    bool $translate = false
): string {
    /** @var Content $content */
    $content = get_content_by_id($contentId);

    if (is_false__($content)) {
        return '';
    }

    $date = new DateTime();

    if ($gmt) {
        $theTime = get_user_datetime($content->publishedGmt);
    } else {
        $theTime = $content->published;
    }

    $theTime = $date->db2Date($format, $theTime, $translate);

    if (is_false__($theTime)) {
        return '';
    }

    /**
     * Filters the content published time.
     *
     * @file App/Shared/Helpers/content.php
     * @param string $theTime The content's formatted time.
     * @param bool   $format   Format to use for retrieving the time the content was written.
     *                         Accepts 'G', 'U', or php date format. Default 'U'.
     * @param bool   $gmt      Whether to retrieve the GMT time. Default false.
     */
    return Filter::getInstance()->applyFilter('get_content_published_time', $theTime, $format, $gmt);
}

/**
 * Retrieves content published time.
 *
 * @file App/Shared/Helpers/content.php
 * @param string $contentId Content id.
 * @param string $format Format to use for retrieving the time the content was published.
 *                       Accepts 'G', 'U', or php date format value specified
 *                       in 'time_format' option. Default empty.
 * @return string Formatted content published time string or Unix timestamp
 *                if $format is 'U' or 'G'. '' on failure.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function the_published_time(string $contentId, string $format = ''): string
{
    $content = get_content_by_id($contentId);

    if (is_false__($content)) {
        return '';
    }

    if ('' === $format) {
        $theTime = get_content_published_time(
            $contentId,
            get_user_time_format(),
            true,
            true
        );
    } else {
        $theTime = get_content_published_time($contentId, $format, true, true);
    }

    if (is_false__($theTime)) {
        return '';
    }

    /**
     * Filters the time the content was published.
     *
     * @file App/Shared/Helpers/content.php
     * @param string    $theTime  The formatted time.
     * @param string    $format   Format to use for retrieving the time the content was published.
     *                            Accepts 'G', 'U', or php date format value specified
     *                            in 'time_format' option. Default empty.
     * @param object    $content  Content object.
     */
    return Filter::getInstance()->applyFilter('content_published_time', $theTime, $format, $content);
}

/**
 * A function which retrieves content modified date.
 *
 * @file App/Shared/Helpers/content.php
 * @param string $contentId Content id.
 * @param string $format Format to use for retrieving the date the content was modified.
 *                        Accepts 'G', 'U', or php date format value specified
 *                        in 'date_format' option. Default 'U'.
 * @param bool $gmt Whether to use GMT. Default false.
 * @param bool $translate Whether the returned string should be translated. Default false.
 * @return string Formatted content modified date string or Unix timestamp
 *                if $format is 'U' or 'G'. '' on failure.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_content_modified_date(
    string $contentId,
    string $format = 'U',
    bool $gmt = false,
    bool $translate = false
): string {
    /** @var Content $content */
    $content = get_content_by_id($contentId);

    if (is_false__($content)) {
        return '';
    }

    $date = new DateTime();

    if ($gmt) {
        $theDate = get_user_datetime($content->modifiedGmt);
    } else {
        $theDate = $content->modified;
    }

    $theDate = $date->db2Date($format, $theDate, $translate);

    if (is_false__($theDate)) {
        return '';
    }

    /**
     * Filters the content modified date.
     *
     * @file App/Shared/Helpers/content.php
     * @param string $theDate The content's formatted date.
     * @param bool   $format  Format to use for retrieving the date the content was published.
     *                        Accepts 'G', 'U', or php date format. Default 'U'.
     * @param bool   $gmt     Whether to retrieve the GMT date. Default false.
     */
    return Filter::getInstance()->applyFilter('get_content_modified_date', $theDate, $format, $gmt);
}

/**
 * Retrieves content published date.
 *
 * @file App/Shared/Helpers/content.php
 * @param string $contentId Content id.
 * @param string $format Format to use for retrieving the date the content was published.
 *                       Accepts 'G', 'U', or php date format value specified
 *                       in 'date_format' option. Default empty.
 * @return string Formatted content modified date string or Unix timestamp
 *                if $format is 'U' or 'G'. '' on failure.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function the_modified_date(string $contentId, string $format = ''): string
{
    /** @var Content $content */
    $content = get_content_by_id($contentId);

    if (is_false__($content)) {
        return '';
    }

    if ('' === $format) {
        $theDate = get_content_modified_date(
            $contentId,
            get_user_date_format(),
            true,
            true
        );
    } else {
        $theDate = get_content_modified_date($contentId, $format, true, true);
    }

    if (is_false__($theDate)) {
        return '';
    }

    /**
     * Filters the date the content was modified.
     *
     * @file App/Shared/Helpers/content.php
     * @param string    $theDate The formatted date.
     * @param string    $format  Format to use for retrieving the date the content was modified.
     *                           Accepts 'G', 'U', or php date format value specified
     *                           in 'date_format' option. Default empty.
     * @param object    $content Content object.
     */
    return Filter::getInstance()->applyFilter('content_modified_date', $theDate, $format, $content);
}

/**
 * A function which retrieves content modified time.
 *
 * @file App/Shared/Helpers/content.php
 * @param string $contentId Content id.
 * @param string $format Format to use for retrieving the time the content was modified.
 *                       Accepts 'G', 'U', or php date format value specified
 *                       in 'date_format' option. Default 'U'.
 * @param bool $gmt Whether to use GMT. Default false.
 * @param bool $translate Whether the returned string should be translated. Default false.
 * @return string Formatted content modified time string or Unix timestamp
 *                if $format is 'U' or 'G'. '' on failure.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_content_modified_time(
    string $contentId,
    string $format = 'U',
    bool $gmt = false,
    bool $translate = false
): string {
    /** @var Content $content */
    $content = get_content_by_id($contentId);

    if (is_false__($content)) {
        return '';
    }

    $date = new DateTime();

    if ($gmt) {
        $theTime = get_user_datetime($content->modifiedGmt);
    } else {
        $theTime = $content->modified;
    }

    $theTime = $date->db2Date($format, $theTime, $translate);

    if (is_false__($theTime)) {
        return '';
    }

    /**
     * Filters the content modified time.
     *
     * @file App/Shared/Helpers/content.php
     * @param string $theTime The content's formatted time.
     * @param bool   $format   Format to use for retrieving the time the content was modified.
     *                         Accepts 'G', 'U', or php date format. Default 'U'.
     * @param bool   $gmt      Whether to retrieve the GMT time. Default false.
     */
    return Filter::getInstance()->applyFilter('get_content_modified_time', $theTime, $format, $gmt);
}

/**
 * Retrieves content modified time.
 *
 * @file App/Shared/Helpers/content.php
 * @param string $contentId Content id.
 * @param string $format Format to use for retrieving the time the content was modified.
 *                       Accepts 'G', 'U', or php date format value specified
 *                       in 'time_format' option. Default empty.
 * @return string Formatted content modified time string or Unix timestamp
 *                if $format is 'U' or 'G'. '' on failure.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function the_modified_time(string $contentId, string $format = ''): string
{
    /** @var Content $content */
    $content = get_content_by_id($contentId);

    if (is_false__($content)) {
        return '';
    }

    if ('' === $format) {
        $theTime = get_content_modified_time(
            $contentId,
            get_user_time_format(),
            true,
            true
        );
    } else {
        $theTime = get_content_modified_time($contentId, $format, true, true);
    }

    if (is_false__($theTime)) {
        return '';
    }

    /**
     * Filters the time the content was modified.
     *
     * @file App/Shared/Helpers/content.php
     * @param string    $theTime The formatted time.
     * @param string    $format  Format to use for retrieving the time the content was modified.
     *                           Accepts 'G', 'U', or php date format value specified
     *                           in 'time_format' option. Default empty.
     * @param object    $content Content object.
     */
    return Filter::getInstance()->applyFilter('content_modified_time', $theTime, $format, $content);
}

/**
 * A function which retrieves content content_type id.
 *
 * Purpose of this function is for the `content_content_type_id`
 * filter.
 *
 * @file App/Shared/Helpers/content.php
 * @param string $contentId Content id.
 * @return string Content Type id or '' on failure.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 * @throws TypeException
 */
function get_content_content_type_id(string $contentId): string
{
    /** @var Content $content */
    $content = get_content_by_id($contentId);

    if (is_false__($content)) {
        return '';
    }

    $contentTypeId = ContentType::fromNative($content->type)->contentTypeId()->toNative();
    /**
     * Filters the content content_type id.
     *
     * @file App/Shared/Helpers/content.php
     * @param string $contentTypeId The content's content_type id.
     * @param string $contentId  The content ID.
     */
    return Filter::getInstance()->applyFilter('content_content_type_id', $contentTypeId, $contentId);
}

/**
 * A function which retrieves content content_type.
 *
 * Purpose of this function is for the `content_content_type`
 * filter.
 *
 * @file App/Shared/Helpers/content.php
 * @param string $contentId Content id.
 * @return string Content Type or '' on failure
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_content_contenttype(string $contentId): string
{
    /** @var Content $content */
    $content = get_content_by_id($contentId);

    if (is_false__($content)) {
        return '';
    }

    $contenttype = $content->type;
    /**
     * Filters the content content_type.
     *
     * @file App/Shared/Helpers/content.php
     * @param string   $contenttype  The content's content_type.
     * @param string   $contentId    The content ID.
     */
    return Filter::getInstance()->applyFilter('content_content_type', $contenttype, $contentId);
}

/**
 * A function which retrieves a content's parent id.
 *
 * Purpose of this function is for the `content_parent_id`
 * filter.
 *
 * @file App/Shared/Helpers/content.php
 * @param string $contentId Content id.
 * @return string Content parent id or '' on failure.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 * @throws TypeException
 */
function get_content_parent_id(string $contentId): string
{
    /** @var Content $content */
    $content = get_content_by_id($contentId);

    if (is_false__($content)) {
        return '';
    }

    $parentId = \App\Domain\Content\Content::fromNative($content->parent)->contentId()->toNative();
    /**
     * Filters the content parent id.
     *
     * @file App/Shared/Helpers/content.php
     * @param string $parentId  The content's parent id.
     * @param string $contentId The content ID.
     */
    return Filter::getInstance()->applyFilter('content_parent_id', $parentId, $contentId);
}

/**
 * A function which retrieves content parent.
 *
 * Purpose of this function is for the `content_parent`
 * filter.
 *
 * @file App/Shared/Helpers/content.php
 * @param string $contentId Content id.
 * @return string Content parent or '' on failure.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_content_parent(string $contentId): string
{
    /** @var Content $content */
    $content = get_content_by_id($contentId);

    if (is_false__($content)) {
        return '';
    }

    $parent = $content->parent;
    /**
     * Filters the content parent.
     *
     * @file App/Shared/Helpers/content.php
     * @param string $parent    The content's parent.
     * @param string $contentId The content ID.
     */
    return Filter::getInstance()->applyFilter('content_parent', $parent, $contentId);
}

/**
 * A function which retrieves content sidebar.
 *
 * Purpose of this function is for the `content_sidebar`
 * filter.
 *
 * @file App/Shared/Helpers/content.php
 * @param string $contentId Content id.
 * @return int Content sidebar integer or 0 on failure.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_content_sidebar(string $contentId): int
{
    /** @var Content $content */
    $content = get_content_by_id($contentId);

    if (is_false__($content)) {
        return (int) 0;
    }

    $sidebar = $content->sidebar;
    /**
     * Filters the content sidebar.
     *
     * @file App/Shared/Helpers/content.php
     * @param int    $sidebar   The content's sidebar option.
     * @param string $contentId The content ID.
     */
    return Filter::getInstance()->applyFilter('content_sidebar', (int) $sidebar, $contentId);
}

/**
 * A function which retrieves content show in menu.
 *
 * Purpose of this function is for the `content_show_in_menu`
 * filter.
 *
 * @file App/Shared/Helpers/content.php
 * @param string $contentId Content id.
 * @return int Content show in menu integer or 0 on failure.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_content_show_in_menu(string $contentId): int
{
    /** @var Content $content */
    $content = get_content_by_id($contentId);

    if (is_false__($content)) {
        return (int) 0;
    }

    $menu = $content->showInMenu;
    /**
     * Filters the content show in menu.
     *
     * @file App/Shared/Helpers/content.php
     * @param int    $menu      The content's show in menu option.
     * @param string $contentId The content ID.
     */
    return Filter::getInstance()->applyFilter('content_show_in_menu', (int) $menu, $contentId);
}

/**
 * A function which retrieves content show in search.
 *
 * Purpose of this function is for the `content_show_in_search`
 * filter.
 *
 * @file App/Shared/Helpers/content.php
 * @param string $contentId Content id.
 * @return int Content show in search integer or 0 on failure.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_content_show_in_search(string $contentId): int
{
    $content = get_content_by_id($contentId);

    if (is_false__($content)) {
        return (int) 0;
    }

    $search = $content->showInSearch;
    /**
     * Filters the content show in search.
     *
     * @file App/Shared/Helpers/content.php
     * @param int    $search    The content's show in search option.
     * @param string $contentId The content ID.
     */
    return Filter::getInstance()->applyFilter('content_show_in_search', (int) $search, $contentId);
}

/**
 * Creates a unique content slug.
 *
 * @file App/Shared/Helpers/content.php
 * @param string $originalSlug Original slug of content.
 * @param string $originalTitle Original title of content.
 * @param string|null $contentId Unique content id or null.
 * @param string|null $contentType Content type of content.
 * @return string Unique content slug.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function cms_unique_content_slug(
    string $originalSlug,
    string $originalTitle,
    ?string $contentId = null,
    ?string $contentType = null
): string {
    if (is_null__($contentId)) {
        $contentSlug = cms_slugify($originalTitle, 'content');
    } elseif (if_content_slug_exists($contentId, $originalSlug, $contentType)) {
        $contentSlug = cms_slugify($originalTitle, 'content');
    } else {
        $contentSlug = $originalSlug;
    }
    /**
     * Filters the unique content slug before returned.
     *
     * @file App/Shared/Helpers/content.php
     * @param string    $contentSlug   Unique content slug.
     * @param string    $originalSlug  The content's original slug.
     * @param string    $originalTitle The content's original title before slugified.
     * @param string    $contentId     The content's unique id.
     * @param string    $contentType   The content's content type.
     */
    return Filter::getInstance()->applyFilter(
        'cms_unique_content_slug',
        $contentSlug,
        $originalSlug,
        $originalTitle,
        $contentId,
        $contentType
    );
}

/**
 * Insert or update a content.
 *
 * All the `$contentdata` array fields have filters associated with the values. The filters
 * have the prefix 'pre_' followed by the field name. An example using 'content_status' would have
 * the filter called, 'pre_content_status' that can be hooked into.
 *
 * @file App/Shared/Helpers/content.php
 * @param array|ServerRequestInterface|Content $contentdata An array of data that is used for insert or update.
 *
 *      @type string $contentTitle The content's title.
 *      @type string $contentBody The content's body.
 *      @type string $contentSlug The content's slug.
 *      @type string $contentAuthor The content's author.
 *      @type string $contentType The content's contenttype.
 *      @type string $contentParent The content's parent.
 *      @type string $contentSidebar The content's sidebar.
 *      @type string $contentShowInMenu Whether to show content in menu.
 *      @type string $contentShowInSearch Whether to show content in search.
 *      @type string $contentRelativeUrl The content's relative url.
 *      @type string $contentFeaturedImage THe content's featured image.
 *      @type string $contentStatus THe content's status.
 *      @type string $contentPublished Timestamp describing the moment when the content
 *                                     was published. Defaults to Y-m-d h:i A.
 * @return Error|string|null The newly created content's content_id or throws an error or returns null
 *                     if the content could not be created or updated.
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
function cms_insert_content(array|ServerRequestInterface|Content $contentdata): Error|string|null
{
    $userId = get_current_user_id();

    $defaults = [
        'title' => '',
        'body' => '',
        'author' => $userId,
        'type' => '',
        'parent' => '',
        'sidebar' => '0',
        'showInMenu' => '0',
        'showInSearch' => '0',
        'featuredImage' => '',
        'status' => 'draft'
    ];

    if ($contentdata instanceof ServerRequestInterface) {
        $contentdata = $contentdata->getParsedBody();
    } elseif ($contentdata instanceof Content) {
        $contentdata = $contentdata->toArray();
    }

    $contentdata = Utils::parseArgs($contentdata, $defaults);

    // Are we updating or creating?
    if (!empty($contentdata['id']) && !is_false__(get_content_by_id($contentdata['id']))) {
        $update = true;
        $contentId = new ContentId($contentdata['id']);
        /** @var Content $contentBefore */
        $contentBefore = get_content_by('id', $contentId->toNative());

        if (is_false__($contentBefore)) {
            return new ContentError(message: esc_html__(string: 'Invalid content id.', domain: 'devflow'));
        }

        $previousStatus = get_content_status($contentId->toNative());
        /**
         * Fires immediately before content is inserted into the content document.
         *
         * @param string $previousStatus Status of the content before it is created or updated.
         * @param string $contentId      The content's content_id.
         * @param bool   $update         Whether this is an existing content or a new content.
         */
        Action::getInstance()->doAction('content_previous_status', $previousStatus, $contentId->toNative(), $update);

        /**
         * Create new content object.
         */
        $content = new Content();
        $content->id = $contentId->toNative();
    } else {
        $update = false;
        $contentId = new ContentId();
        $previousStatus = 'new';
        /**
         * Fires immediately before a content is inserted into the content document.
         *
         * @param string $previousStatus Status of the content before it is created or updated.
         * @param string $contentId      The content's content_id.
         * @param bool   $update         Whether this is an existing content or a new content.
         */
        Action::getInstance()->doAction('content_previous_status', $previousStatus, $contentId->toNative(), $update);

        /**
         * Create new content object.
         */
        $content = new Content();
        $content->id = $contentId->toNative();
    }

    if (isset($contentdata['title'])) {
        $contentTitle = $contentdata['title'];
    } else {
        /**
         * For an update, don't modify the title if it
         * wasn't supplied as an argument.
         */
        $contentTitle = $contentBefore->title;
    }

    $rawContentType = $contentdata['type'];
    $sanitizedContentType = Sanitizer::item($rawContentType);
    /**
     * Filters a content's type before the content is created or updated.
     *
     * @param string $sanitizedContentType Content type after it has been sanitized.
     * @param string $rawContentType The content's content type.
     */
    $contentType = Filter::getInstance()->applyFilter(
        'pre_content_type',
        $sanitizedContentType,
        $rawContentType
    );
    $content->type = $contentType;

    $rawContentTitle = $contentTitle;
    $sanitizedContentTitle = Sanitizer::item($rawContentTitle);
    /**
     * Filters a content's title before created/updated.
     *
     * @param string $sanitizedContentTitle Content title after it has been sanitized.
     * @param string $rawContentTitle The content's title.
     */
    $contentTitle = Filter::getInstance()->applyFilter(
        'pre_content_title',
        (string) $sanitizedContentTitle,
        (string) $rawContentTitle
    );
    $content->title = $contentTitle;

    if (isset($contentdata['slug'])) {
        /**
         * cms_unique_content_slug will take the original slug supplied and check
         * to make sure that it is unique. If not unique, it will make it unique
         * by adding a number at the end.
         */
        $contentSlug = cms_unique_content_slug(
            $contentdata['slug'],
            $contentTitle,
            $contentId->toNative(),
            $contentType
        );
    } else {
        /**
         * For an update, don't modify the slug if it
         * wasn't supplied as an argument.
         */
        $contentSlug = $contentBefore->slug;
    }

    $rawContentSlug = $contentSlug;
    $sanitizedContentSlug = Sanitizer::item($rawContentSlug);
    /**
     * Filters a content's slug before created/updated.
     *
     * @param string $sanitizedContentSlug Content slug after it has been sanitized.
     * @param string $rawContentSlug The content's slug.
     */
    $contentSlug = Filter::getInstance()->applyFilter(
        'pre_content_slug',
        (string) $sanitizedContentSlug,
        (string) $rawContentSlug
    );
    $content->slug = $contentSlug;

    $rawContentBody = $contentdata['body'];
    /**
     * Filters a content's body before created/updated.
     *
     * @param string $rawContentSlug The content's slug.
     */
    $contentBody = Filter::getInstance()->applyFilter(
        'pre_content_body',
        $rawContentBody
    );
    $content->body = $contentBody;

    /**
     * Check for content author
     *
     * @param string $contentAuthor Content author id.
     */
    $contentAuthor = $contentdata['author'];

    if ($contentAuthor === '' || $contentAuthor === null) {
        return new ContentError(
            message: esc_html__(string: 'Content author cannot be null or empty.', domain: 'devflow')
        );
    }

    $content->author = $contentAuthor;

    $rawContentParent = $contentdata['parent'];
    $sanitizedContentParent = Sanitizer::item($rawContentParent);
    /**
     * Filters a content's parent before the content is created or updated.
     *
     * @param string $sanitizedContentParent Content parent after it has been sanitized.
     * @param string $rawContentParent The content's parent.
     */
    $contentParent = Filter::getInstance()->applyFilter(
        'pre_content_parent',
        $sanitizedContentParent,
        $rawContentParent
    );
    $content->parent = $contentParent;

    $rawContentSidebar = $contentdata['sidebar'];
    $sanitizedContentSidebar = Sanitizer::item($rawContentSidebar, 'int');
    /**
     * Filters a content's sidebar before the content is created or updated.
     *
     * @param int $sanitizedContentSidebar Content sidebar after it has been sanitized.
     * @param int $rawContentSidebar The content's sidebar.
     */
    $contentSidebar = Filter::getInstance()->applyFilter(
        'pre_content_sidebar',
        $sanitizedContentSidebar,
        $rawContentSidebar
    );
    $content->sidebar = $contentSidebar;

    $rawContentShowInMenu = $contentdata['showInMenu'];
    $sanitizedContentShowInMenu = Sanitizer::item($rawContentShowInMenu, 'int');
    /**
     * Filters a content's show in menu before the content is created or updated.
     *
     * @param string $sanitizedContentShowInMenu Content show in menu after it has been sanitized.
     * @param int $rawContentShowInMenu The content's show in menu.
     */
    $contentShowInMenu = Filter::getInstance()->applyFilter(
        'pre_content_show_in_menu',
        $sanitizedContentShowInMenu,
        $rawContentShowInMenu
    );
    $content->showInMenu = $contentShowInMenu;

    $rawContentShowInSearch = $contentdata['showInSearch'];
    $sanitizedContentShowInSearch = Sanitizer::item($rawContentShowInSearch, 'int');
    /**
     * Filters a content's show in search before the content is created or updated.
     *
     * @param int $sanitizedContentShowInSearch Content show in search after it has been sanitized.
     * @param int $rawContentShowInSearch The content's show in search.
     */
    $contentShowInSearch = Filter::getInstance()->applyFilter(
        'pre_content_show_in_search',
        $sanitizedContentShowInSearch,
        $rawContentShowInSearch
    );
    $content->showInSearch = $contentShowInSearch;

    $rawContentFeaturedImage = cms_optimized_image_upload($contentdata['featuredImage']);
    $sanitizedContentFeaturedImage = Sanitizer::item($rawContentFeaturedImage);
    /**
     * Filters a content's featured image before the content is created or updated.
     *
     * @param string $sanitizedContentFeaturedImage Content featured image after it has been sanitized.
     * @param string $rawContentFeaturedImage The content's featured image.
     */
    $contentFeaturedImage = Filter::getInstance()->applyFilter(
        'pre_content_featured_image',
        (string) $sanitizedContentFeaturedImage,
        (string) $rawContentFeaturedImage
    );
    $content->featuredImage = $contentFeaturedImage;

    $rawContentStatus = $contentdata['status'];
    $sanitizedContentStatus = Sanitizer::item($rawContentStatus);
    /**
     * Filters a content's status before the content is created or updated.
     *
     * @param string $sanitizedContentStatus Content status after it has been sanitized.
     * @param string $rawContentStatus The content's status.
     */
    $contentStatus = Filter::getInstance()->applyFilter(
        'pre_content_status',
        (string) $sanitizedContentStatus,
        (string) $rawContentStatus
    );
    $content->status = $contentStatus;

    /*
     * Filters whether the content is null.
     *
     * @param bool  $maybe_empty Whether the content should be considered "null".
     * @param array $contentdata   Array of content data.
     */
    $maybeNull = !$contentTitle && !$contentBody;
    if (Filter::getInstance()->applyFilter('cms_insert_empty_content', $maybeNull, $contentdata)) {
        return new ContentError(message: esc_html__(string: 'The title and content are null.', domain: 'devflow'));
    }

    if (!$update) {
        if (empty($contentdata['published']) || php_like('%0000-00-00 00:00', $contentdata['published'])) {
            $contentPublished = (new DateTime('now', get_user_timezone()))->getDateTime();
            $contentPublishedGmt = (new DateTime('now', 'GMT'))->getDateTime();
            $contentCreated = $contentPublished;
            $contentCreatedGmt = $contentPublishedGmt;
        } else {
            $contentPublished = (new DateTime(
                str_replace(['AM', 'PM'], '', $contentdata['published']),
                get_user_timezone()
            ))->getDateTime();
            $contentPublishedGmt = (new DateTime($contentdata['publishedGmt'] ?? 'now', 'GMT'))->getDateTime();
            $contentCreated = $contentPublished;
            $contentCreatedGmt = $contentPublishedGmt;
        }
    } else {
        $contentPublished = (new DateTime(
            str_replace(['AM', 'PM'], '', $contentdata['published']),
            get_user_timezone()
        ))->getDateTime();
        $contentPublishedGmt = (new DateTime(
            $contentdata['publishedGmt'] ?? str_replace(['AM', 'PM'], '', $contentdata['published']),
            get_user_timezone()
        ))->getDateTime();
        $contentCreated = $contentPublished;
        $contentCreatedGmt = $contentPublishedGmt;
        $contentModified = (new DateTime(QubusDateTimeImmutable::now(get_user_timezone())->toDateTimeString()))
                ->getDateTime();
        $contentModifiedGmt = (new DateTime(QubusDateTimeImmutable::now('GMT')->toDateTimeString()))->getDateTime();
    }

    $contentDataArray = [
        'id' => $contentId->toNative(),
        'slug' => $contentSlug,
        'body' => $contentBody,
        'author' => $contentAuthor,
        'type' => $contentType,
        'parent' => $contentParent,
        'sidebar' => (string) $contentSidebar,
        'showInMenu' => (string) $contentShowInMenu,
        'showInSearch' => (string) $contentShowInSearch,
        'featuredImage' => $contentFeaturedImage,
        'status' => $contentStatus,
        'created' => $contentCreated->format('Y-m-d H:i:s'),
        'createdGmt' => $contentCreatedGmt->format('Y-m-d H:i:s'),
        'published' => $contentPublished->format('Y-m-d H:i:s'),
        'publishedGmt' => $contentPublishedGmt->format('Y-m-d H:i:s'),
    ];
    $contentData = unslash($contentDataArray);

    // Content custom fields.
    $metaFields = $contentdata['content_field'] ?? [];

    /**
     * Filters content data before the record is created or updated.
     *
     * It only includes data in the content table, not any content metadata.
     *
     * @param array    $contentData
     *     Values and keys for the user.
     *
     *      @type string $contentTitle         The content's title.
     *      @type string $contentBody          The content's body.
     *      @type string $contentSlug          The content's slug.
     *      @type string $contentAuthor        The content's author.
     *      @type string $contentType          The content's type.
     *      @type string $contentParent        The content's parent.
     *      @type string $contentSidebar       The content's sidebar.
     *      @type string $contentShowInMenu    Whether to show content in menu.
     *      @type string $contentShowInSearch  Whether to show content in search.
     *      @type string $contentFeaturedImage The content's featured image.
     *      @type string $contentStatus        The content's status.
     *      @type string $contentCreated       Timestamp of when the content was created.
     *                                         Defaults to Y-m-d H:i:s A.
     *      @type string $contentCreatedGmt    Timestamp of when the content was created
     *                                         in GMT. Defaults to Y-m-d H:i:s A.
     *      @type string $contentPublished     Timestamp describing the moment when the content
     *                                         was published. Defaults to Y-m-d H:i:s A.
     *      @type string $contentPublishedGmt  Timestamp describing the moment when the content
     *                                         was published in GMT. Defaults to Y-m-d H:i:s A.
     *      @type string $contentModified      Timestamp of when the content was modified.
     *                                         Defaults to Y-m-d H:i:s A.
     *      @type string $contentModifiedGmt   Timestamp of when the content was modified
     *                                         in GMT. Defaults to Y-m-d H:i:s A.
     *
     * @param bool     $update Whether the content is being updated rather than created.
     * @param string|null $id  ID of the content to be updated, or NULL if the content is being created.
     */
    Filter::getInstance()->applyFilter(
        'cms_before_insert_content_data',
        $contentData,
        $update,
        $update ? $contentBefore->id : $contentId,
    );

    $resolver = new NativeCommandHandlerResolver(
        container: ContainerFactory::make(config: config(key: 'commandbus.container'))
    );
    $odin = new Odin(bus: new SynchronousCommandBus($resolver));

    if (!$update) {
        /**
         * Fires immediately before a content is inserted into the content document.
         *
         * @param Content $content Content object.
         */
        Action::getInstance()->doAction('pre_content_insert', $content);

        try {
            $command = new CreateContentCommand([
                'contentId' => ContentId::fromString($contentId->toNative()),
                'contentTitle' => new StringLiteral($contentTitle),
                'contentSlug' => new StringLiteral($contentSlug),
                'contentBody' => new StringLiteral($contentBody ?? ''),
                'contentAuthor' => UserId::fromString($contentAuthor),
                'contentTypeSlug' => new StringLiteral($contentType),
                'contentParent' => 'NULL' !== $contentParent ? ContentId::fromString($contentParent) : null,
                'contentSidebar' => new IntegerNumber($contentSidebar),
                'contentShowInMenu' => new IntegerNumber($contentShowInMenu),
                'contentShowInSearch' => new IntegerNumber($contentShowInSearch),
                'contentFeaturedImage' => new StringLiteral($contentFeaturedImage),
                'meta' => new ArrayLiteral($metaFields),
                'contentStatus' => new StringLiteral($contentStatus),
                'contentCreated' => $contentCreated,
                'contentCreatedGmt' => $contentCreatedGmt,
                'contentPublished' => $contentPublished,
                'contentPublishedGmt' => $contentPublishedGmt,
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
                    'Content Function' => 'cms_insert_content'
                ]
            );

            return new ContentError(message: esc_html__(
                string: 'Could not insert content into the content table.',
                domain: 'devflow'
            ));
        }

    } else {
        /**
         * Fires immediately before existing content is updated in the content document.
         *
         * @param string  $contentId Content id.
         * @param content $content   Content object.
         */
        Action::getInstance()->doAction('pre_content_update', $contentId, $content);

        try {
            $command = new UpdateContentCommand([
                'contentId' => ContentId::fromString($contentId->toNative()),
                'contentTitle' => new StringLiteral($contentTitle),
                'contentSlug' => new StringLiteral($contentSlug),
                'contentBody' => new StringLiteral($contentBody),
                'contentAuthor' => UserId::fromString($contentAuthor),
                'contentTypeSlug' => new StringLiteral($contentType),
                'contentParent' => 'NULL' !== $contentParent ? ContentId::fromString($contentParent) : null,
                'contentSidebar' => new IntegerNumber($contentSidebar),
                'contentShowInMenu' => new IntegerNumber($contentShowInMenu),
                'contentShowInSearch' => new IntegerNumber($contentShowInSearch),
                'contentFeaturedImage' => new StringLiteral($contentFeaturedImage),
                'meta' => new ArrayLiteral($metaFields),
                'contentStatus' => new StringLiteral($contentStatus),
                'contentPublished' => $contentPublished,
                'contentPublishedGmt' => $contentPublishedGmt,
                'contentModified' => $contentModified,
                'contentModifiedGmt' => $contentModifiedGmt,
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
                    'Content Function' => 'cms_insert_content'
                ]
            );

            return new ContentError(message: esc_html__(
                string: 'Could not update content within the content table.',
                domain: 'devflow'
            ));
        }
    }

    if (!empty($metaFields)) {
        foreach ($metaFields as $key => $value) {
            update_contentmeta($contentId->toNative(), $key, $value);
        }
    }

    /** @var Content $content */
    $content = get_content_by_id($contentId->toNative());

    ContentCachePsr16::clean($content);

    if ($update) {
        /**
         * Action hook triggered after existing content has been updated.
         *
         * @param string $contentId Content id.
         * @param array  $content   Content object.
         */
        Action::getInstance()->doAction('update_content', $contentId, $content);
        /** @var Content $contentAfter */
        $contentAfter = get_content_by_id($contentId->toNative());
        /**
         * Action hook triggered after existing content has been updated.
         *
         * @param string $contentId      Content id.
         * @param object $contentAfter   Content object following the update.
         * @param object $contentBefore  Content object before the update.
         */
        Action::getInstance()->doAction('content_updated', $contentId->toNative(), $contentAfter, $contentBefore);
    } else {
        /**
         * Action hook triggered after content is created.
         *
         * @param array $content Content object.
         */
        Action::getInstance()->doAction('create_content', $content);
    }

    /**
     * Action hook triggered after content has been saved.
     *
     * The dynamic portion of this hook, `$contentType`, is the content's
     * content type.
     *
     * @param string $contentId The content's id.
     * @param array $content    Content object.
     * @param bool  $update     Whether this is an existing content or a new content.
     */
    Action::getInstance()->doAction("save_content_{$contentType}", $contentId->toNative(), $content, $update);

    /**
     * Action hook triggered after content has been saved.
     *
     * The dynamic portions of this hook, `$contentType` and `$contentStatus`,
     * are the content's content type and status.
     *
     * @param string $contentId The content's id.
     * @param array  $content   Content object.
     * @param bool   $update    Whether this is existing content or new content.
     */
    Action::getInstance()->doAction(
        "save_content_{$contentType}_{$contentStatus}",
        $contentId->toNative(),
        $content,
        $update
    );

    /**
     * Action hook triggered after content has been saved.
     *
     * @param string $contentId The content's id.
     * @param object $content   Content object.
     * @param bool   $update    Whether this is existing content or new content.
     */
    Action::getInstance()->doAction('cms_after_insert_content_data', $contentId->toNative(), $content, $update);

    return $contentId->toNative();
}

/**
 * Update a content in the content document.
 *
 * See {@see cms_insert_content()} For what fields can be set in $contentdata.
 *
 * @file App/Shared/Helpers/content.php
 * @param array|ServerRequestInterface|Content $contentdata An array of content data or a content object.
 * @return string|Error The updated content's id or return Error if content could not be updated.
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
function cms_update_content(array|ServerRequestInterface|Content $contentdata): string|Error
{
    if ($contentdata instanceof ServerRequestInterface) {
        $contentdata = $contentdata->getParsedBody();
    } elseif ($contentdata instanceof Content) {
        $contentdata = $contentdata->toArray();
    }

    // First, get all the original fields.
    /** @var Content $content */
    $content = get_content_by_id($contentdata['id']);

    if (is_null__($content->id) || '' === $content->id) {
        return new ContentError(message: esc_html__(string: 'Invalid content id.', domain: 'devflow'));
    }

    // Merge old and new fields with new fields overwriting old ones.
    $contentdata = array_merge($content->toArray(), $contentdata);

    return cms_insert_content($contentdata);
}

/**
 * Deletes content from the content document.
 *
 * @file App/Shared/Helpers/content.php
 * @param string $contentId The id of the content to delete.
 * @return bool|Content Content on success or false on failure.
 * @throws CommandCouldNotBeHandledException
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 * @throws TypeException
 * @throws UnresolvableCommandHandlerException
 * @throws CommandPropertyNotFoundException
 */
function cms_delete_content(string $contentId): Content|bool
{
    /** @var Content $content */
    $content = get_content_by_id($contentId);

    if (is_false__($content)) {
        return false;
    }

    $resolver = new NativeCommandHandlerResolver(
        container: ContainerFactory::make(config: config(key: 'commandbus.container'))
    );
    $odin = new Odin(bus: new SynchronousCommandBus($resolver));

    /**
     * Action hook fires before a content is deleted.
     *
     * @param string $contentId Content id.
     */
    Action::getInstance()->doAction('before_delete_content', $contentId);

    if (is_content_parent($contentId)) {
        foreach (is_content_parent($contentId) as $parent) {
            try {
                $command = new RemoveContentParentCommand([
                    'contentId' => ContentId::fromString($contentId),
                    'contentParent' => ContentId::fromString($parent['content_id']),
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
                        'Content Function' => 'cms_delete_content'
                    ]
                );
                return false;
            }
        }
    }

    $contentMetaKeys = get_contentmeta($contentId);
    if ($contentMetaKeys) {
        foreach ($contentMetaKeys as $metaKey => $metaValue) {
            delete_contentmeta($contentId, $metaKey, $metaValue);
        }
    }

    /**
     * Action hook fires immediately before a content is deleted from the
     * content document.
     *
     * @param string $contentId Content ID.
     */
    Action::getInstance()->doAction('delete_content', $contentId);

    try {
        $command = new DeleteContentCommand([
            'contentId' => ContentId::fromString($content->id),
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
                'Content Function' => 'cms_delete_content'
            ]
        );
    }

    /**
     * Action hook fires immediately after a content is deleted from the content document.
     *
     * @param string $contentId Content id.
     */
    Action::getInstance()->doAction('deleted_content', $contentId);

    if (is_content_parent($contentId)) {
        foreach (is_content_parent($contentId) as $children) {
            ContentCachePsr16::clean((array) $children);
        }
    }

    /**
     * Action hook fires after a content is deleted.
     *
     * @param string $contentId Content id.
     */
    Action::getInstance()->doAction('after_delete_content', $contentId);

    return $content;
}

/**
 * Returns the number of content rows within a given content type.
 *
 * @file App/Shared/Helpers/content.php
 * @param string $slug Content type slug.
 * @return int Number of content rows based on content type.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function number_content_by_type(string $slug): int
{
    $dfdb = dfdb();

    try {
        $count = $dfdb->getVar(
            $dfdb->prepare("SELECT COUNT(*) FROM {$dfdb->prefix}content WHERE content_type = ?", [$slug])
        );

        return (int) $count;
    } catch (PDOException | TypeException $ex) {
        FileLoggerFactory::getLogger()->error(
            sprintf(
                'SQLSTATE[%s]: %s',
                $ex->getCode(),
                $ex->getMessage()
            ),
            [
                'Content Function' => 'number_content_per_type'
            ]
        );
    }
    return (int) 0;
}

/**
 * Retrieves all posts
 *
 * @file App/Shared/Helpers/content.php
 * @access private
 * @param string|null $parentId Content parent id
 * @param string $contentId Content id.
 * @return void
 * @throws ReflectionException
 * @throws UnresolvableQueryHandlerException
 */
function get_content_parent_dropdown_list(string $parentId = null, string $contentId = ''): void
{
    $resolver = new NativeQueryHandlerResolver(
        container: ContainerFactory::make(config: config(key: 'querybus.aliases'))
    );
    $enquirer = new Enquire(bus: new SynchronousQueryBus($resolver));

    try {
        $query = new FindContentQuery();

        $content = $enquirer->execute($query);

        foreach ($content as $value) {
            if ($contentId !== $value['id']) {
                echo '<option value="' . $value['id'] . '"' .
                selected($parentId, $value['id'], false) . '>' .
                $value['title'] . '</option>';
            }
        }
    } catch (PDOException $ex) {
        FileLoggerFactory::getLogger()->error(
            sprintf(
                'SQLSTATE[%s]: %s',
                $ex->getCode(),
                $ex->getMessage()
            ),
            [
                'Content Function' => 'get_content_parent_dropdown_list'
            ]
        );
    }
}

/**
 * Retrieves an array of css class names.
 *
 * @file App/Shared/Helpers/content.php
 * @param string $contentId Content id of current content.
 * @param string|array $class One or more css class names to add to html element list.
 * @return array An array of css class names.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_content_class(string $contentId, string|array $class = ''): array
{
    /** @var Content $content */
    $content = get_content_by_id($contentId);

    $classes = [];

    if ($class) {
        if (!is_array($class)) {
            $class = preg_split('#\s+#', $class);
        }
        $classes = array_map('\Qubus\Security\Helpers\esc_attr', $class);
    } else {
        $class = [];
    }

    if (!$content) {
        return $classes;
    }

    $classes[] = 'content-' . $content->id;
    $classes[] = 'contenttype-' . $content->type;

    $classes = array_map('\Qubus\Security\Helpers\esc_attr', $classes);
    /**
     * Filters the list of CSS class names for the current content.
     *
     * @param array $classes An array of css class names.
     * @param array $class   An array of additional css class names.
     * @param string $contentId Content id of the current content.
     */
    $classes = Filter::getInstance()->applyFilter('content_class', $classes, $class, $content->id);

    return array_unique($classes);
}

/**
 * Displays the permalink for the current content.
 *
 * Uses `the_permalink` filter.
 *
 * @file App/Shared/Helpers/content.php
 * @param string|Content|ContentId $content Content object or content id.
 * @return string Content permalink.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function the_permalink(string|Content|ContentId $content): string
{
    if ($content instanceof Content) {
        $content = $content->id;
    }

    if ($content instanceof ContentId) {
        $content = $content->toNative();
    }

    /**
     * Filters the display of the permalink for the current content.
     *
     * @file App/Shared/Helpers/content.php
     * @param string         $permalink The permalink for the current content.
     * @param string|Content $content   Content object or id.
     */
    return Filter::getInstance()->applyFilter('the_permalink', get_permalink($content), $content);
}

/**
 * The cms content filter.
 *
 * Uses `the_body` filter.
 *
 * @file App/Shared/Helpers/content.php
 * @param string|Content|ContentId $content Content object or content id.
 * @return string Content body.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function the_body(string|Content|ContentId $content): string
{
    if ($content instanceof Content) {
        $content = $content->id;
    }

    if ($content instanceof ContentId) {
        $content = $content->toNative();
    }

    $contentBody = get_content_body($content);
    $contentBody = Filter::getInstance()->applyFilter('the_body', $contentBody);
    $contentBody = str_replace(']]>', ']]&gt;', $contentBody);
    return $contentBody;
}

/**
 * Retrieves and displays content meta value.
 *
 * Uses `the_meta` filter.
 *
 * @file App/Shared/Helpers/content.php
 * @param string|Content|ContentId $content Content object or id.
 * @param string $key Content meta key.
 * @return string Content meta value.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function the_meta(string|Content|ContentId $content, string $key): string
{
    if ($content instanceof Content) {
        $content = $content->id;
    }

    if ($content instanceof ContentId) {
        $content = $content->toNative();
    }

    $theMeta = get_contentmeta(contentId: $content, key: $key, single: true);
    /**
     * Filters content meta.
     *
     * @file App/Shared/Helpers/content.php
     * @param mixed  $theMeta Content meta value.
     * @param string $key     Content meta key.
     */
    return Filter::getInstance()->applyFilter('the_meta', $theMeta, $key);
}

/**
 * Retrieves and displays content title.
 *
 * Uses `the_title` filter.
 *
 * @file App/Shared/Helpers/content.php
 * @param string|Content|ContentId $content Content object or id.
 * @return string Content meta value.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function the_title(string|Content|ContentId $content): string
{
    if ($content instanceof Content) {
        $content = $content->id;
    }

    if ($content instanceof ContentId) {
        $content = $content->toNative();
    }

    $theTitle = get_content_title($content);
    /**
     * Filters content meta.
     *
     * @file App/Shared/Helpers/content.php
     * @param mixed  $theTitle Content title.
     */
    return Filter::getInstance()->applyFilter('the_title', $theTitle);
}

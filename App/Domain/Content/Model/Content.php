<?php

declare(strict_types=1);

namespace App\Domain\Content\Model;

use App\Infrastructure\Persistence\Cache\ContentCachePsr16;
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

use function Codefy\Framework\Helpers\config;
use function md5;
use function Qubus\Security\Helpers\esc_html;
use function Qubus\Security\Helpers\purify_html;
use function Qubus\Support\Helpers\convert_array_to_object;
use function Qubus\Support\Helpers\is_null__;

/**
 * @property string $id
 * @property string $title
 * @property string $slug
 * @property string $body
 * @property string $author
 * @property string $type
 * @property string $parent
 * @property string $sidebar
 * @property string $showInMenu
 * @property string $showInSearch
 * @property string $relativeUrl
 * @property string $featuredImage
 * @property string $status
 * @property string $created
 * @property string $createdGmt
 * @property string published
 * @property string $publishedGmt
 * @property string $modified
 * @property string $modifiedGmt
 */
final class Content extends stdClass
{
    use HydratorAware;

    public function __construct(protected ?Database $dfdb = null)
    {
    }

    /**
     * Return only the main user fields.
     *
     * @param string $field The field to query against: 'id', 'ID', 'slug' or 'type'.
     * @param string $value The field value
     * @return object|false Raw user object
     * @throws Exception
     * @throws ReflectionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws InvalidArgumentException
     */
    public function findBy(string $field, string $value): Content|false
    {
        if ('' === $value) {
            return false;
        }

        $contentId = match ($field) {
            'id', 'ID' => $value,
            'slug' => SimpleCacheObjectCacheFactory::make(namespace: $this->dfdb->prefix . 'contentslug')
                    ->get(md5($value), ''),
            'type' => SimpleCacheObjectCacheFactory::make(namespace: $this->dfdb->prefix . 'contenttype')
                    ->get(md5($value), ''),
            default => false,
        };

        $dbField = match ($field) {
            'id', 'ID' => 'content_id',
            'slug' => 'content_slug',
            'type' => 'content_type',
            default => false,
        };

        $content = null;

        if ('' !== $contentId) {
            if (
                $data = SimpleCacheObjectCacheFactory::make(namespace: $this->dfdb->prefix . 'content')
                    ->get(md5($contentId))
            ) {
                is_array($data) ? convert_array_to_object($data) : $data;
            }
        }

        if (
                !$data = $this->dfdb->getRow(
                    $this->dfdb->prepare(
                        "SELECT * 
                            FROM {$this->dfdb->prefix}content 
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
            $content = $this->create($data);
            ContentCachePsr16::update($content);
        }

        if (is_array($content)) {
            $content = convert_array_to_object($content);
        }

        return $content;
    }

    /**
     * Create a new instance of Content. Optionally populating it
     * from a data array.
     *
     * @param array $data
     * @return Content
     */
    public function create(array $data = []): Content
    {
        $content = $this->__create();
        if ($data) {
            $content = $this->populate($content, $data);
        }
        return $content;
    }

    /**
     * Create a new Content object.
     *
     * @return Content
     */
    protected function __create(): Content
    {
        return new Content();
    }

    public function populate(Content $content, array $data = []): self
    {
        if (config(key: 'cms.relative_url') === 'contenttype') {
            $relativeUrl = $data['content_type'] . '/' . $data['content_slug'] . '/';
        } else {
            $relativeUrl = $data['content_slug'] . '/';
        }

        $content->id = esc_html(string: $data['content_id']) ?? null;
        $content->title = esc_html(string: $data['content_title']) ?? null;
        $content->slug = esc_html(string: $data['content_slug']) ?? null;
        $content->body = purify_html(string: $data['content_body']) ?? null;
        $content->author = isset($data['content_author']) ? esc_html(string: $data['content_author']) : null;
        $content->type = esc_html($data['content_type']) ?? null;
        $content->parent = isset($data['content_parent']) ? esc_html(string: $data['content_parent']) : null;
        $content->sidebar = esc_html(string: (string) $data['content_sidebar']) ?? null;
        $content->showInMenu = esc_html(string: (string) $data['content_show_in_menu']) ?? null;
        $content->showInSearch = esc_html(string: (string) $data['content_show_in_search']) ?? null;
        $content->relativeUrl = esc_html(string: $relativeUrl) ?? null;

        $content->featuredImage = isset($data['content_featured_image']) ?
        esc_html(string: $data['content_featured_image']) :
        null;

        $content->status = esc_html(string: $data['content_status']) ?? null;
        $content->created = isset($data['content_created']) ? esc_html(string: $data['content_created']) : null;

        $content->createdGmt = isset($data['content_created_gmt']) ?
        esc_html(string: $data['content_created_gmt']) :
        null;

        $content->published = isset($data['content_published']) ? esc_html(string: $data['content_published']) : null;

        $content->publishedGmt = isset($data['content_published_gmt']) ?
        esc_html(string: $data['content_published_gmt']) :
        null;

        $content->modified = isset($data['content_modified']) ? esc_html(string: $data['content_modified']) : null;

        $content->modifiedGmt = isset($data['content_modified_gmt']) ?
        esc_html(string: $data['content_modified_gmt']) :
        null;

        return $content;
    }

    /**
     * Magic method for checking the existence of a certain custom field.
     *
     * @param string $key Content meta key to check if set.
     * @return bool Whether the given content meta key is set.
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

        return MetaData::factory(Registry::getInstance()->get('tblPrefix') . 'contentmeta')
                ->exists('content', $this->id, Registry::getInstance()->get('tblPrefix') . $key);
    }

    /**
     * Magic method for accessing custom fields.
     *
     * @param string $key Content meta key to retrieve.
     * @return string Value of the given content meta key (if set). If `$key` is 'id', the content ID.
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
            $value = MetaData::factory(Registry::getInstance()->get('tblPrefix') . 'contentmeta')
                    ->read('content', $this->id, Registry::getInstance()->get('tblPrefix') . $key, true);
        }

        return purify_html($value);
    }

    /**
     * Magic method for setting custom content fields.
     *
     * This method does not update custom fields in the content document. It only stores
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
     * Retrieves from the content and contentmeta table.
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
     * Consults the content and contentmeta tables.
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
        unset($this->dfdb);

        return get_object_vars($this);
    }
}

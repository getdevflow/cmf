<?php

declare(strict_types=1);

namespace App\Shared\Helpers;

use App\Application\Devflow;
use App\Domain\ContentType\Model\ContentType;
use App\Domain\Content\Command\UpdateContentAuthorCommand;
use App\Domain\Content\Query\FindContentQuery;
use App\Domain\Content\ValueObject\ContentId;
use App\Domain\Product\Model\Product;
use App\Domain\Site\Command\UpdateSiteOwnerCommand;
use App\Domain\Site\Model\Site;
use App\Domain\Site\Query\FindSitesByOwnerQuery;
use App\Domain\Site\ValueObject\SiteId;
use App\Domain\User\Model\User;
use App\Domain\User\ValueObject\UserId;
use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Services\Options;
use App\Shared\Services\DateTime;
use App\Shared\Services\MetaData;
use App\Shared\Services\Registry;
use App\Shared\Services\Sanitizer;
use App\Shared\Services\SimpleCacheObjectCacheFactory;
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
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Exception\BadFormatException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use Defuse\Crypto\Key;
use PDOException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Qubus\EventDispatcher\ActionFilter\Filter;
use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Exception;
use Qubus\Http\Session\SessionException;
use Qubus\NoSql\NodeQ;
use Qubus\Support\Inflector;
use ReflectionException;

use function Codefy\Framework\Helpers\config;
use function Codefy\Framework\Helpers\mail;
use function Codefy\Framework\Helpers\storage_path;
use function count;
use function Qubus\Security\Helpers\esc_html;
use function Qubus\Security\Helpers\esc_html__;
use function Qubus\Support\Helpers\is_false__;
use function sprintf;

/**
 * Global database function.
 *
 * @return Database
 * @throws ContainerExceptionInterface
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function dfdb(): Database
{
    return Registry::getInstance()->get('dfdb');
}

/**
 * Returns the object subtype for a given array ID of a specific type.
 *
 * @file App/Shared/Helpers/db.php
 * @param string $type Type of array to request metadata for. (e.g. content, user, product).
 * @param string $id ID of the array to retrieve its subtype.
 * @return string The array subtype or an empty string if unspecified subtype.
 * @throws CommandPropertyNotFoundException
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 * @throws TypeException
 * @throws UnresolvableQueryHandlerException
 */
function get_object_subtype(string $type, string $id): string
{
    $objectSubtype = '';

    if ($type === 'content') {
        /** @var ContentType $contentType */
        $contentType = get_content_type_by('id', $id);
        if (is_false__($contentType)) {
            return '';
        }
        $objectSubtype = $contentType->slug;
    } elseif ($type === 'user') {
        /** @var User $user */
        $user = get_user_by(field: 'id', value: $id);
        if (is_false__($user)) {
            return '';
        }
        $objectSubtype = 'user';
    } elseif ($type === 'site') {
        /** @var Site $site */
        $site = get_site_by('id', $id);
        if (is_false__($site)) {
            return '';
        }
        $objectSubtype = 'site';
    } elseif ($type === 'product') {
        /** @var Product $product */
        $product = get_product_by('id', $id);
        if (is_false__($product)) {
            return '';
        }
        $objectSubtype = 'product';
    }

    return Filter::getInstance()->applyFilter("get_object_subtype_{$type}", $objectSubtype, $id);
}

/**
 * Creates unique slug based on string
 *
 * @file App/Shared/Helpers/db.php
 * @param string $title Text to be slugified.
 * @param string $table Table the text is saved to (i.e. content, contenttype, site, product)
 * @return string Slug.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function cms_slugify(string $title, string $table): string
{
    $dfdb = dfdb();

    $sanitizedTable = Sanitizer::item($table);
    /**
     * Instantiate the slugify class.
     */
    $slug = Inflector::slugify($title);
    /**
     * Slug field to filter by based on table
     * being called.
     */
    $field = "{$sanitizedTable}_slug";
    $titles = [];
    /**
     * Query content/content_type/site/product.
     */
    if ($sanitizedTable === 'site') {
        $table = $dfdb->basePrefix . $sanitizedTable;
    } else {
        $table = $dfdb->prefix . $sanitizedTable;
    }

    $slugVar = "$slug%";
    $sql = "SELECT *"
    . " FROM ?"
    . " WHERE ? LIKE ?";

    $results = $dfdb->getResults(
        $dfdb->prepare($sql, [$table, $field, $slugVar]),
        Database::ARRAY_A
    );
    if (count($results) > 0) {
        foreach ($results as $item) {
            $titles[] = $item["$field"];
        }
    }
    $total = count($titles);
    $last = end($titles);
    /**
     * No equal results, return $slug
     */
    if ($total === 0) {
        return $slug;
    } elseif ($total === 1) { // If we have only one result, we look if it has a number at the end.
        /**
         * Take the only value of the array, because there is only 1.
         */
        $exists = $titles[0];
        /**
         * Kill the slug and see what happens
         */
        $exists = str_replace($slug, "", $exists);
        /**
         * If there is no light about, there was no number at the end.
         * We added it now
         */
        if ("" === trim($exists)) {
            return $slug . "-1";
        } else { // If not..........
            /**
             * Obtain the number because of REGEX it will be there... ;-)
             */
            $number = str_replace("-", "", $exists);
            /**
             * Number plus one.
             */
            $number++;
            return $slug . "-" . $number;
        }
    } else { // If there is more than one result, we need the last one.
        /**
         * Last value
         */
        $exists = $last;
        /**
         * Delete the actual slug and see what happens
         */
        $exists = str_replace($slug, "", $exists);
        /**
         * Obtain the number, easy.
         */
        $number = str_replace("-", "", $exists);
        /**
         * Increment number +1
         */
        $number++;
        return $slug . "-" . $number;
    }
}

/**
 * Returns a list of internal links for TinyMCE.
 *
 * @file App/Shared/Helpers/db.php
 * @return object[]
 * @throws ReflectionException
 * @throws UnresolvableQueryHandlerException
 */
function tinymce_link_list(): array
{
    $resolver = new NativeQueryHandlerResolver(
        container: ContainerFactory::make(config: config(key: 'querybus.aliases'))
    );
    $enquirer = new Enquire(bus: new SynchronousQueryBus($resolver));

    $query = new FindContentQuery();

    $results = $enquirer->execute($query);

    if (empty($results)) {
        return [];
    }

    return $results;
}

/**
 * Checks if a slug exists among records from the content type table.
 *
 * @file App/Shared/Helpers/db.php
 * @param string $contentTypeId Content Type id to check against.
 * @param string $slug Slug to search for.
 * @return bool Returns true if content type slug exists or false otherwise.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function if_content_type_slug_exists(string $contentTypeId, string $slug): bool
{
    $dfdb = dfdb();

    try {
        $exist = $dfdb->getVar(
            $dfdb->prepare(
                "SELECT COUNT(*) FROM {$dfdb->prefix}content_type WHERE content_type_slug = ? AND content_type_id <> ?",
                [
                    $slug,
                    $contentTypeId
                ]
            )
        );

        return $exist > 0;
    } catch (PDOException $e) {
        FileLoggerFactory::getLogger()->error(
            sprintf(
                'SQLSTATE[%s]: %s',
                $e->getCode(),
                $e->getMessage()
            ),
            [
                'Db Function' => 'if_content_type_slug_exists'
            ]
        );
    }

    return false;
}

/**
 * Checks if a slug exists among records from the content table.
 *
 * @file App/Shared/Helpers/db.php
 * @param string $contentId Content id to check against or null.
 * @param string $slug Slug to search for.
 * @param string $contentType The content type to filter.
 * @return bool Returns true if content slug exists or false otherwise.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function if_content_slug_exists(string $contentId, string $slug, string $contentType): bool
{
    $dfdb = dfdb();

    try {
        $exist = $dfdb->getVar(
            $dfdb->prepare(
                "SELECT COUNT(*) FROM {$dfdb->prefix}content 
                WHERE content_slug = ? AND content_id <> ? AND content_type = ?",
                [
                    $slug,
                    $contentId,
                    $contentType
                ]
            )
        );

        return $exist > 0;
    } catch (PDOException $e) {
        FileLoggerFactory::getLogger()->error(
            sprintf(
                'SQLSTATE[%s]: %s',
                $e->getCode(),
                $e->getMessage()
            ),
            [
                'Db Function' => 'if_content_slug_exists'
            ]
        );
    }

    return false;
}

/**
 * Checks if a site slug exists.
 *
 * @file App/Shared/Helpers/db.php
 * @param string $siteId Site id to check against.
 * @param string $slug Slug to search for.
 * @return bool Returns true if site slug exists or false otherwise.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function if_site_slug_exists(string $siteId, string $slug): bool
{
    $dfdb = dfdb();

    try {
        $exist = $dfdb->getVar(
            $dfdb->prepare(
                "SELECT COUNT(*) FROM {$dfdb->basePrefix}site WHERE site_slug = ? AND site_id <> ?",
                [
                    $slug,
                    $siteId
                ]
            )
        );

        return $exist > 0;
    } catch (PDOException $e) {
        FileLoggerFactory::getLogger()->error(
            sprintf(
                'SQLSTATE[%s]: %s',
                $e->getCode(),
                $e->getMessage()
            ),
            [
                'Db Function' => 'if_site_slug_exists'
            ]
        );
    }

    return false;
}

/**
 * Checks if a product slug exists.
 *
 * @file App/Shared/Helpers/db.php
 * @param string $productId Product id to check against.
 * @param string $slug Slug to search for.
 * @return bool Returns true if site slug exists or false otherwise.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function if_product_slug_exists(string $productId, string $slug): bool
{
    $dfdb = dfdb();

    try {
        $exist = $dfdb->getVar(
            $dfdb->prepare(
                "SELECT COUNT(*) FROM {$dfdb->prefix}product WHERE product_slug = ? AND product_id <> ?",
                [
                    $slug,
                    $productId
                ]
            )
        );

        return $exist > 0;
    } catch (PDOException $e) {
        FileLoggerFactory::getLogger()->error(
            sprintf(
                'SQLSTATE[%s]: %s',
                $e->getCode(),
                $e->getMessage()
            ),
            [
                'Db Function' => 'if_product_slug_exists'
            ]
        );
    }

    return false;
}

/**
 * Checks if content has any children.
 *
 * @file App/Shared/Helpers/db.php
 * @param string $contentId Content id to check.
 * @return bool|array| False if content has no children or array of children if true.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function is_content_parent(string $contentId): bool|array
{
    $dfdb = dfdb();

    try {
        $children = $dfdb->getResults(
            $dfdb->prepare(
                "SELECT * FROM {$dfdb->prefix}content WHERE content_parent = ?",
                [
                    $contentId
                ]
            ),
            Database::ARRAY_A
        );

        if (count($children) <= 0) {
            return false;
        }

        return $children;
    } catch (PDOException $e) {
        FileLoggerFactory::getLogger()->error(
            sprintf(
                'SQLSTATE[%s]: %s',
                $e->getCode(),
                $e->getMessage()
            ),
            [
                'Db Function' => 'is_content_parent'
            ]
        );
    }

    return false;
}

/**
 * Checks if a given content type exists on content.
 *
 * @file App/Shared/Helpers/db.php
 * @param string $contentType Content Type slug to check for.
 * @return bool Returns true if content type exists or false otherwise.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function if_content_type_exists(string $contentType): bool
{
    $dfdb = dfdb();

    try {
        $exist = $dfdb->getVar(
            $dfdb->prepare(
                "SELECT COUNT(*) FROM {$dfdb->prefix}content WHERE content_type = ?",
                [
                    $contentType
                ]
            )
        );

        return $exist > 0;
    } catch (PDOException $e) {
        FileLoggerFactory::getLogger()->error(
            sprintf(
                'SQLSTATE[%s]: %s',
                $e->getCode(),
                $e->getMessage()
            ),
            [
                'Db Function' => 'if_contents_content_type_exists'
            ]
        );
    }

    return false;
}

/**
 * Reassigns content to a different user.
 *
 * @file App/Shared/Helpers/db.php
 * @param string $userId ID of user being removed.
 * @param string $assignId ID of user to whom content will be assigned.
 * @param string $contentId ID of the content.
 * @return bool
 * @throws CommandPropertyNotFoundException
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 * @throws SessionException
 * @throws TypeException
 * @throws CommandCouldNotBeHandledException
 * @throws UnresolvableCommandHandlerException
 */
function reassign_content(string $userId, string $assignId, string $contentId): bool
{
    $dfdb = dfdb();

    try {
        $count = $dfdb->getVar(
            $dfdb->prepare(
                "SELECT COUNT(*) FROM {$dfdb->prefix}content WHERE content_author = ?",
                [
                    $userId
                ]
            )
        );
    } catch (PDOException $e) {
        FileLoggerFactory::getLogger()->error(
            sprintf(
                'SQLSTATE[%s]: %s',
                $e->getCode(),
                $e->getMessage()
            ),
            [
                'Db Function' => 'reassign_content'
            ]
        );
    }

    if ($count > 0) {
        try {

            $resolver = new NativeCommandHandlerResolver(
                container: ContainerFactory::make(config: config(key: 'commandbus.container'))
            );
            $odin = new Odin(bus: new SynchronousCommandBus($resolver));

            $command = new UpdateContentAuthorCommand([
                'contentId' => ContentId::fromString($contentId),
                'contentAuthor' => UserId::fromString($assignId),
                'contentModified' => (new DateTime(time: 'now', timezone: config(key: 'app.timezone'))),
                'contentModifiedGmt' => (new DateTime())->gmtdate(),
            ]);

            $odin->execute($command);

            return true;
        } catch (PDOException $e) {
            Devflow::inst()::$APP->flash->error(
                sprintf(
                    esc_html__(
                        string: 'Reassign content error: %s',
                        domain: 'devflow'
                    ),
                    $e->getMessage()
                )
            );
        }
    }

    return false;
}

/**
 * Reassigns sites to a different user.
 *
 * @file App/Shared/Helpers/db.php
 * @param string $userId ID of user being removed.
 * @param array $params User parameters (assign_id and role).
 * @return bool
 * @throws CommandCouldNotBeHandledException
 * @throws CommandPropertyNotFoundException
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 * @throws SessionException
 * @throws TypeException
 * @throws UnresolvableCommandHandlerException
 */
function reassign_sites(string $userId, array $params = []): bool
{
    $dfdb = dfdb();

    if ('' === $userId) {
        return false;
    }

    try {
        $count = $dfdb->getVar(
            $dfdb->prepare(
                "SELECT COUNT(*) FROM {$dfdb->basePrefix}site WHERE site_owner = ?",
                [
                    $userId
                ]
            )
        );
    } catch (PDOException $e) {
        FileLoggerFactory::getLogger()->error(
            sprintf(
                'SQLSTATE[%s]: %s',
                $e->getCode(),
                $e->getMessage()
            ),
            [
                'Db Function' => 'reassign_sites'
            ]
        );
    }

    if ($count > 0) {
        try {
            $resolver = new NativeCommandHandlerResolver(
                container: ContainerFactory::make(config: config(key: 'commandbus.container'))
            );
            $odin = new Odin(bus: new SynchronousCommandBus($resolver));

            $command = new UpdateSiteOwnerCommand([
                'siteId' => SiteId::fromString($params['siteId']),
                'siteOwner' => UserId::fromString($params['assignId']),
                'siteModified' => (new DateTime(time: 'now', timezone: config(key: 'app.timezone'))),
            ]);

            $odin->execute($command);

            return true;
        } catch (PDOException $e) {
            Devflow::inst()::$APP->flash->error(
                sprintf(
                    esc_html__(
                        string: 'Reassign site error: %s',
                        domain: 'devflow'
                    ),
                    $e->getMessage()
                )
            );
        }
    }

    return false;
}

/**
 * Checks if the requested user is an admin of any sites or has any admin roles.
 *
 * @file App/Shared/Helpers/db.php
 * @param string $userId ID of user to check.
 * @return bool Returns true if user has sites and false otherwise.
 * @throws CommandPropertyNotFoundException
 * @throws Exception
 * @throws ReflectionException
 * @throws TypeException
 * @throws UnresolvableQueryHandlerException
 * @throws ContainerExceptionInterface
 * @throws NotFoundExceptionInterface
 */
function if_user_has_sites(string $userId): bool
{
    if ('' === $userId) {
        return false;
    }

    $resolver = new NativeQueryHandlerResolver(
        container: ContainerFactory::make(config: config(key: 'querybus.aliases'))
    );
    $enquirer = new Enquire(bus: new SynchronousQueryBus($resolver));

    $query = new FindSitesByOwnerQuery([
        'siteOwner' => UserId::fromString($userId),
    ]);

    $results = $enquirer->execute($query);

    if (!empty($results)) {
        return true;
    }

    $option = get_user_option('role', $userId);
    if ($option === 'super' || $option === 'admin') {
        return true;
    }
    return false;
}

/**
 * Get an array of sites by owner.
 *
 * @file App/Shared/Helpers/db.php
 * @param string $userId The owner's id.
 * @return array
 * @throws CommandPropertyNotFoundException
 * @throws ReflectionException
 * @throws UnresolvableQueryHandlerException
 */
function get_owner_sites(string $userId): array
{
    if ('' === $userId) {
        return [];
    }

    $resolver = new NativeQueryHandlerResolver(
        container: ContainerFactory::make(config: config(key: 'querybus.aliases'))
    );
    $enquirer = new Enquire(bus: new SynchronousQueryBus($resolver));

    $query = new FindSitesByOwnerQuery([
        'userId' => $userId,
    ]);

    return $enquirer->execute($query);
}

/**
 * Populate the option cache.
 *
 * @access private
 *
 * @file App/Shared/Helpers/db.php
 * @return bool
 * @throws ContainerExceptionInterface
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function populate_options_cache(): bool
{
    $dfdb = dfdb();

    try {
        $options = $dfdb->getResults(query: "SELECT * FROM {$dfdb->prefix}option", output: Database::ARRAY_A);
        foreach ($options as $option) {
            SimpleCacheObjectCacheFactory::make(
                namespace: $dfdb->prefix . 'options'
            )->set($option['option_key'], $option['option_value']);
        }
        return true;
    } catch (PDOException $e) {
        FileLoggerFactory::getLogger()->error(
            sprintf(
                'SQLSTATE[%s]: %s',
                $e->getCode(),
                $e->getMessage()
            ),
            [
                'Db Function' => 'populate_options_cache'
            ]
        );
    }

    return false;
}

/**
 * Populate the usermeta cache.
 *
 * @access private
 *
 * @file App/Shared/Helpers/db.php
 * @return bool
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function populate_usermeta_cache(): bool
{
    $dfdb = dfdb();

    try {
        $umeta = $dfdb->getResults(query: "SELECT * FROM {$dfdb->basePrefix}usermeta", output: Database::ARRAY_A);
        foreach ($umeta as $meta) {
            MetaData::factory(namespace: $dfdb->prefix . 'usermeta')
                ->updateMetaDataCache(metaType: 'user', metaTypeIds: [$meta['user_id']]);
        }
        return true;
    } catch (PDOException $e) {
        FileLoggerFactory::getLogger()->error(
            sprintf(
                'SQLSTATE[%s]: %s',
                $e->getCode(),
                $e->getMessage()
            ),
            [
                'Db Function' => 'populate_usermeta_cache'
            ]
        );
    }

    return false;
}

/**
 * Populate the contentmeta cache.
 *
 * @access private
 *
 * @file App/Shared/Helpers/db.php
 * @return bool
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function populate_contentmeta_cache(): bool
{
    $dfdb = dfdb();

    try {
        $pmeta = $dfdb->getResults(query: "SELECT * FROM {$dfdb->prefix}contentmeta", output: Database::ARRAY_A);
        foreach ($pmeta as $meta) {
            MetaData::factory(namespace: $dfdb->prefix . 'contentmeta')
                ->updateMetaDataCache(metaType: 'content', metaTypeIds: [$meta['content_id']]);
        }
        return true;
    } catch (PDOException $e) {
        FileLoggerFactory::getLogger()->error(
            sprintf(
                'SQLSTATE[%s]: %s',
                $e->getCode(),
                $e->getMessage()
            ),
            [
                'Db Function' => 'populate_contentmeta_cache'
            ]
        );
    }

    return false;
}

/**
 * Populate the productmeta cache.
 *
 * @access private
 *
 * @file App/Shared/Helpers/db.php
 * @return bool
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function populate_productmeta_cache(): bool
{
    $dfdb = dfdb();

    try {
        $pmeta = $dfdb->getResults(query: "SELECT * FROM {$dfdb->prefix}productmeta", output: Database::ARRAY_A);
        foreach ($pmeta as $meta) {
            MetaData::factory(namespace: $dfdb->prefix . 'productmeta')
                    ->updateMetaDataCache(metaType: 'product', metaTypeIds: [$meta['product_id']]);
        }
        return true;
    } catch (PDOException $e) {
        FileLoggerFactory::getLogger()->error(
            sprintf(
                'SQLSTATE[%s]: %s',
                $e->getCode(),
                $e->getMessage()
            ),
            [
                'Db Function' => 'populate_productmeta_cache'
            ]
        );
    }

    return false;
}

/**
 * Login Details Email
 *
 * Function used to send login details to new
 * user.
 *
 * @file App/Shared/Helpers/db.php
 * @throws ContainerExceptionInterface
 * @throws EnvironmentIsBrokenException
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 * @throws SessionException
 */
function cms_nodeq_login_details(): void
{
    $option = Options::factory();

    $table = 'login';
    $nodeq = NodeQ::open(storage_path("app/queue/{$table}"));

    $sql = $nodeq->where('sent', (int) 0)->get();

    if (count(array_filter($sql)) === 0) {
        foreach ($sql as $r) {
            $nodeq->where('_id', esc_html($r['_id']))->delete();
        }
    }

    if (count($sql) > 0) {
        foreach ($sql as $r) {
            $siteName = $option->read(optionKey: 'sitename');
            /** @var User $user */
            $user = get_userdata($r['userid']);
            try {
                $password = Crypto::decrypt(
                    $r['pass'],
                    Key::loadFromAsciiSafeString(config('auth.encryption_key'))
                );

                $message = esc_html__(string: 'Hi there,', domain: 'devflow') . "<br />";
                $message .= "<p>" . sprintf(
                    esc_html__(
                        string: "Welcome to %s! Here's how to log in: ",
                        domain: 'devflow'
                    ),
                    $siteName
                );
                $message .= $r['login_url'] . "</p>";
                $message .= sprintf(esc_html__(string: 'Username: %s', domain: 'devflow'), $user->login) . "<br />";
                $message .= sprintf(esc_html__(string: 'Password: %s', domain: 'devflow'), $password) . "<br />";
                $message .= "<p>" . sprintf(
                    esc_html__(
                        string: 'If you have any problems, please contact us at <a href="mailto:%s">%s</a>.',
                        domain: 'devflow'
                    ),
                    $option->read(optionKey: 'admin_email'),
                    $option->read(optionKey: 'admin_email')
                ) . "</p>";

                $message = process_email_html($message, esc_html__(string: 'New Account', domain: 'devflow'));
                $headers[] = sprintf("From: %s <auto-reply@%s>", $siteName, $r['domain_name']);
                $headers[] = 'Content-Type: text/html; charset="UTF-8"';
                $headers[] = sprintf("X-Mailer: Devflow %s", Devflow::inst()->release());
                try {
                    mail(
                        $user->email,
                        sprintf(
                            esc_html__(string: '[%s] New Account', domain: 'devflow'),
                            $siteName
                        ),
                        $message,
                        $headers
                    );
                } catch (\PHPMailer\PHPMailer\Exception $e) {
                    Devflow::inst()::$APP->flash->error($e->getMessage());
                }

                $upd = $nodeq->where('_id', esc_html($r['_id']));
                $upd->update([
                    'sent' => 1
                ]);
            } catch (BadFormatException $e) {
                FileLoggerFactory::getLogger()->error(
                    sprintf(
                        'CRYPTOFORMAT[%s]: %s',
                        $e->getCode(),
                        $e->getMessage()
                    )
                );
            } catch (WrongKeyOrModifiedCiphertextException $e) {
                FileLoggerFactory::getLogger()->error(
                    sprintf(
                        'CRYPTOKEY[%s]: %s',
                        $e->getCode(),
                        $e->getMessage()
                    )
                );
            }
        }
    }
}

/**
 * Reset Password Email
 *
 * Function used to send reset password to a user.
 *
 * @file App/Shared/Helpers/db.php
 * @throws ContainerExceptionInterface
 * @throws EnvironmentIsBrokenException
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function cms_nodeq_reset_password(): void
{
    $option = Options::factory();

    $table = 'password_reset';
    $nodeq = NodeQ::open(storage_path("app/queue/{$table}"));

    $sql = $nodeq->where('sent', (int) 0)->get();

    if (count($sql) === 0) {
        foreach ($sql as $r) {
            $nodeq->where('_id', esc_html($r['_id']))
                ->delete();
        }
    }

    if (count($sql) > 0) {
        foreach ($sql as $r) {
            $siteName = $option->read(optionKey: 'sitename');
            /** @var User $user */
            $user = get_userdata($r['userid']);
            try {
                $password = Crypto::decrypt(
                    $r['pass'],
                    Key::loadFromAsciiSafeString(config('auth.encryption_key'))
                );

                $message = esc_html__(string: 'Hi there,', domain: 'devflow') . "<br />";
                $message .= "<p>" . sprintf(
                    esc_html__(
                        string: "Your password has been reset for %s: ",
                        domain: 'devflow'
                    ),
                    $siteName
                );
                $message .= $r['login_url'] . "</p>";
                $message .= sprintf(esc_html__(string: 'Username: %s', domain: 'devflow'), $user->login) . "<br />";
                $message .= sprintf(esc_html__(string: 'Password: %s', domain: 'devflow'), $password) . "<br />";
                $message .= "<p>" . sprintf(
                    esc_html__(
                        string: 'If you have any problems, please contact us at <a href="mailto:%s">%s</a>.',
                        domain: 'devflow'
                    ),
                    $option->read(optionKey: 'admin_email'),
                    $option->read(optionKey: 'admin_email')
                ) . "</p>";

                $message = process_email_html($message, esc_html__(string: 'Password Reset', domain: 'devflow'));
                $headers[] = sprintf("From: %s <auto-reply@%s>", $siteName, $r['domain_name']);
                $headers[] = 'Content-Type: text/html; charset="UTF-8"';
                $headers[] = sprintf("X-Mailer: Devflow %s", Devflow::inst()->release());
                try {
                    mail(
                        $user->email,
                        sprintf(
                            esc_html__(string: '[%s] Password Reset', domain: 'devflow'),
                            $siteName
                        ),
                        $message,
                        $headers
                    );
                } catch (\PHPMailer\PHPMailer\Exception $e) {
                    Devflow::inst()::$APP->flash->error($e->getMessage());
                }

                $upd = $nodeq->where(
                    '_id',
                    esc_html($r['_id'])
                );
                $upd->update([
                    'sent' => 1
                ]);
            } catch (BadFormatException $e) {
                FileLoggerFactory::getLogger()->error(
                    sprintf(
                        'CRYPTOFORMAT[%s]: %s',
                        $e->getCode(),
                        $e->getMessage()
                    )
                );
            } catch (WrongKeyOrModifiedCiphertextException $e) {
                FileLoggerFactory::getLogger()->error(
                    sprintf(
                        'CRYPTOKEY[%s]: %s',
                        $e->getCode(),
                        $e->getMessage()
                    )
                );
            } catch (Exception $e) {
                FileLoggerFactory::getLogger()->error(
                    sprintf(
                        'CRYPTO[%s]: %s',
                        $e->getCode(),
                        $e->getMessage()
                    )
                );
            }
        }
    }
}

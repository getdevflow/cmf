<?php

declare(strict_types=1);

namespace App\Shared\Helpers;

use App\Application\Devflow;
use App\Domain\Site\Command\CreateSiteCommand;
use App\Domain\Site\Command\DeleteSiteCommand;
use App\Domain\Site\Command\UpdateSiteCommand;
use App\Domain\Site\Model\Site;
use App\Domain\Site\Query\FindSiteByIdQuery;
use App\Domain\Site\Query\FindSiteByKeyQuery;
use App\Domain\Site\Query\FindSiteBySlugQuery;
use App\Domain\Site\Query\FindSitesQuery;
use App\Domain\Site\SiteError;
use App\Domain\Site\ValueObject\SiteId;
use App\Domain\User\Model\User;
use App\Domain\User\Query\FindMultisiteUniqueUsersQuery;
use App\Domain\User\ValueObject\UserId;
use App\Infrastructure\Persistence\Cache\UserCachePsr16;
use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Services\Options;
use App\Shared\Services\DateTime;
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
use Codefy\Framework\Codefy;
use Codefy\Framework\Factory\FileLoggerFactory;
use Codefy\QueryBus\Busses\SynchronousQueryBus;
use Codefy\QueryBus\Enquire;
use Codefy\QueryBus\Resolvers\NativeQueryHandlerResolver;
use Codefy\QueryBus\UnresolvableQueryHandlerException;
use DateInvalidTimeZoneException;
use PDOException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Qubus\Error\Error;
use Qubus\EventDispatcher\ActionFilter\Action;
use Qubus\EventDispatcher\ActionFilter\Filter;
use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Exception;
use Qubus\Http\Factories\JsonResponseFactory;
use Qubus\Http\Session\SessionException;
use Qubus\Support\DateTime\QubusDateTimeImmutable;
use Qubus\Support\DateTime\QubusDateTimeZone;
use Qubus\ValueObjects\Identity\Ulid;
use Qubus\ValueObjects\StringLiteral\StringLiteral;
use ReflectionException;

use function array_merge;
use function Codefy\Framework\Helpers\app;
use function Codefy\Framework\Helpers\config;
use function Codefy\Framework\Helpers\public_path;
use function Codefy\Framework\Helpers\resource_path;
use function date;
use function file_get_contents;
use function md5;
use function mkdir;
use function Qubus\Security\Helpers\esc_html;
use function Qubus\Security\Helpers\esc_html__;
use function Qubus\Security\Helpers\unslash;
use function Qubus\Support\Helpers\is_false__;
use function Qubus\Support\Helpers\is_null__;
use function Qubus\Support\Helpers\is_true__;
use function sprintf;
use function str_replace;
use function strtotime;

/**
 * Retrieves all sites.
 *
 * @file App/Shared/Helpers/site.php
 * @throws ReflectionException
 * @throws UnresolvableQueryHandlerException
 */
function get_all_sites(): mixed
{
    $resolver = new NativeQueryHandlerResolver(
        container: ContainerFactory::make(config: config(key: 'querybus.aliases'))
    );
    $enquirer = new Enquire(bus: new SynchronousQueryBus($resolver));

    $query = new FindSitesQuery();

    return $enquirer->execute($query);
}

/**
 * Retrieves site data given a site ID or site object.
 *
 * @file App/Shared/Helpers/site.php
 * @param string $field The field to retrieve the site with (id, key or slug).
 * @param string $value A value for $field.
 * @return object|bool
 * @throws CommandPropertyNotFoundException
 * @throws ReflectionException
 * @throws TypeException
 * @throws UnresolvableQueryHandlerException
 */
function get_site_by(string $field, string $value): object|bool
{
    try {
        $resolver = new NativeQueryHandlerResolver(
            container: ContainerFactory::make(config: config(key: 'querybus.aliases'))
        );
        $enquirer = new Enquire(bus: new SynchronousQueryBus($resolver));

        $query = match ($field) {
            'id' => new FindSiteByIdQuery(['siteId' => new StringLiteral($value)]),
            'key' => new FindSiteByKeyQuery(['siteKey' => new StringLiteral($value)]),
            'slug' => new FindSiteBySlugQuery(['siteSlug' => new StringLiteral($value)]),
        };

        $results = $enquirer->execute($query);

        if (is_null__($results) || is_false__($results)) {
            return false;
        }

        return new Site((array) $results);
    } catch (PDOException $e) {
        FileLoggerFactory::getLogger()->error(
            sprintf(
                'SQLSTATE[%s]: %s',
                $e->getCode(),
                $e->getMessage()
            ),
            [
                'Site Function' => 'get_site_by'
            ]
        );
    }

    return false;
}

/**
 * Checks whether the given site domain exists.
 *
 * @file App/Shared/Helpers/site.php
 * @param string $sitedomain Site domain to check against.
 * @return bool If site domain exists, return true otherwise return false.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function if_site_domain_exists(string $sitedomain): bool
{
    $dfdb = dfdb();

    $site = $dfdb->getVar(
        $dfdb->prepare(
            "SELECT COUNT(*) FROM {$dfdb->basePrefix}site WHERE site_domain = ?",
            [
                $sitedomain
            ]
        )
    );

    return $site > 0;
}

/**
 * Checks whether the given site exists.
 *
 * @file App/Shared/Helpers/site.php
 * @param string $siteDomain Site domain to check against.
 * @param string $sitePath Site path to check against.
 * @return bool If site exists, return true otherwise return false.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function if_site_exists(string $siteDomain, string $sitePath): bool
{
    $dfdb = dfdb();

    $site = $dfdb->getVar(
        $dfdb->prepare(
            "SELECT COUNT(*) FROM {$dfdb->basePrefix}site WHERE site_domain = ? AND site_path = ?",
            [
                $siteDomain,
                $sitePath
            ]
        )
    );

    return $site > 0;
}

/**
 * Adds user meta data for specified site.
 *
 * @access private
 *
 * @file App/Shared/Helpers/site.php
 * @param string $siteKey Site key.
 * @param array $params Parameters to set (assign_id or role).
 * @return bool True if usermete and role is added.
 * @throws CommandPropertyNotFoundException
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 * @throws TypeException
 * @throws UnresolvableQueryHandlerException
 */
function add_site_usermeta(string $siteKey, array $params = []): bool
{
    /** @var User $userdata */
    $userdata = get_userdata($params['assign_id']);
    $data = [
        'bio' => $userdata->bio,
        'status' => $userdata->status,
        'admin_layout' => $userdata->admin_layout <= 0 ? (int) 0 : (int) $userdata->admin_layout,
        'admin_sidebar' => $userdata->admin_sidebar <= 0 ? (int) 0 : (int) $userdata->admin_sidebar,
        'admin_skin' => $userdata->admin_skin === null ? (string) 'skin-red' : (string) $userdata->admin_skin
    ];
    foreach ($data as $metaKey => $metaValue) {
        update_usermeta($params['assign_id'], $siteKey . $metaKey, $metaValue);
    }

    $user = new User(dfdb());
    $user->id = $params['assign_id'];
    $user->setRole((string) $params['role']);

    return true;
}

/**
 * Create the needed directories when a new site is created.
 *
 * @access private
 *
 * @file App/Shared/Helpers/site.php
 * @param string $siteId Site ID.
 * @param Site $site Site object.
 * @param bool $update Whether the site is being created or updated.
 * @return bool True on success or false on failure.
 * @throws CommandPropertyNotFoundException
 * @throws ReflectionException
 * @throws UnresolvableQueryHandlerException|TypeException
 */
function create_site_directories(string $siteId, Site $site, bool $update = false): bool
{
    if (is_true__($update)) {
        return false;
    }

    $site = get_site_by('id', $siteId);
    if (is_false__($site)) {
        return false;
    }

    mkdir(
        directory: public_path(
            path: 'sites' . Codefy::$PHP::DS . $site->key .
            Codefy::$PHP::DS . 'uploads' . Codefy::$PHP::DS  . '__optimized__'
        ),
        permissions: 0755,
        recursive: true
    );

    mkdir(
        directory: public_path(
            path: 'sites' . Codefy::$PHP::DS . $site->key . Codefy::$PHP::DS . '.trash'
        ),
        permissions: 0755,
        recursive: true
    );

    return true;
}

/**
 * Deletes user meta data when site/user is deleted.
 *
 * @access private
 *
 * @file App/Shared/Helpers/site.php
 * @param string $siteId Site Id.
 * @param Site $oldSite Site object.
 * @return bool True on success or false on failure.
 * @throws ContainerExceptionInterface
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 * @throws \Exception
 */
function delete_site_usermeta(string $siteId, Site $oldSite): bool
{
    $dfdb = dfdb();

    if ($siteId !== $oldSite->id) {
        return false;
    }

    try {
        $dfdb->qb()->transactional(function () use ($dfdb, $oldSite) {
            $dfdb->qb()
                ->table(tableName: $dfdb->basePrefix . 'usermeta')
                ->whereLike(columnName: 'meta_key', value: "%$oldSite->key%")
                ->delete();
        });
        $users = get_users_by_site_key($oldSite->key);
        foreach ($users as $user) {
            $_user = new User();
            $_user->id = $user['user_id'];
            $_user->login = $user['user_login'];
            $_user->token = $user['user_token'];
            $_user->email = $user['user_email'];

            UserCachePsr16::clean($_user);
        }

        return true;
    } catch (PDOException | Exception $e) {
        FileLoggerFactory::getLogger()->error(sprintf('ERROR[%s]: %s', $e->getCode(), $e->getMessage()));
    }

    return false;
}

/**
 * Deletes site tables when site is deleted.
 *
 * @access private
 *
 * @file App/Shared/Helpers/site.php
 * @param string $siteId Site ID.
 * @param Site $oldSite Site object.
 * @return bool True on success or false on failure.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function delete_site_tables(string $siteId, Site $oldSite): bool
{
    $dfdb = dfdb();

    if ($siteId !== $oldSite->id) {
        return false;
    }

    $tables = [];
    $sql = $dfdb->getResults(
        $dfdb->prepare(
            "SHOW TABLES LIKE ?",
            [
                "%$oldSite->key%"
            ]
        ),
        Database::ARRAY_A
    );

    foreach ($sql as $value) {
        foreach ($value as $row) {
            $tables[] = $row;
        }
    }

    /**
     * Filters the tables to drop when the site is deleted.
     *
     * @file App/Shared/Helpers/site.php
     * @param array  $tables  Name array of the site tables to be dropped.
     * @param string $siteKey The key of the site to drop tables for.
     */
    $dropTables = Filter::getInstance()->applyFilter('site_drop_tables', $tables, $oldSite->key);

    try {
        $dfdb->qb()->transactional(function () use ($dfdb, $dropTables) {
            $dfdb->qb()->getConnection()->getPdo()->exec(statement: "SET GLOBAL FOREIGN_KEY_CHECKS=0;");

            foreach ((array) $dropTables as $table) {
                $dfdb->qb()->getConnection()->getPdo()->exec(statement: sprintf("DROP TABLE IF EXISTS %s", $table));
            }

            $dfdb->qb()->getConnection()->getPdo()->exec(statement: "SET GLOBAL FOREIGN_KEY_CHECKS=1;");
        });

        return true;
    } catch (PDOException $e) {
        FileLoggerFactory::getLogger()->error(
            sprintf(
                'SQLSTATE[%s]: %s',
                $e->getCode(),
                $e->getMessage()
            ),
            [
                'Site Function' => 'delete_site_tables'
            ]
        );
    }

    return false;
}

/**
 * Deletes the site directory when the site is deleted.
 *
 * @access private
 *
 * @file App/Shared/Helpers/site.php
 * @param string $siteId Site ID.
 * @return bool True on success or false on failure.
 */
function delete_site_directories(string $siteId, Site $oldSite): bool
{
    if ($siteId !== $oldSite->id) {
        return false;
    }

    rmdir__(public_path(path: 'sites' . Codefy::$PHP::DS . $oldSite->key));

    return true;
}

/**
 * Retrieve the current site key.
 *
 * @file App/Shared/Helpers/site.php
 * @return mixed Site key.
 * @throws ReflectionException
 * @throws ContainerExceptionInterface
 * @throws NotFoundExceptionInterface
 */
function get_current_site_key(): mixed
{
    return Registry::getInstance()->get('siteKey');
}

/**
 * Retrieve a list of users based on site.
 *
 * @file App/Shared/Helpers/site.php
 * @return array Users data.
 * @throws ReflectionException
 * @throws UnresolvableQueryHandlerException
 */
function get_multisite_users(): array
{
    $resolver = new NativeQueryHandlerResolver(
        container: ContainerFactory::make(config: config(key: 'querybus.aliases'))
    );
    $enquirer = new Enquire(bus: new SynchronousQueryBus($resolver));

    $query = new FindMultisiteUniqueUsersQuery();

    return $enquirer->execute($query);
}

/**
 * Add user to a site.
 *
 * @file App/Shared/Helpers/site.php
 * @param string|User $user User to add to a site.
 * @param string|Site $site Site to add user to.
 * @param string $role Role to assign to user for this site.
 * @return false|string User id on success or false on failure.
 * @throws CommandPropertyNotFoundException
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 * @throws TypeException
 * @throws UnresolvableQueryHandlerException
 */
function add_user_to_site(User|string $user, Site|string $site, string $role): false|string
{
    if ($user instanceof User) {
        $userdata = $user;
    } else {
        $userdata = get_userdata($user);
    }

    if ($site instanceof Site) {
        $sitedata = $site;
    } else {
        $sitedata = get_site_by('id', $site);
    }

    if (!username_exists($userdata->login)) {
        return false;
    }

    if (!if_site_exists($sitedata->domain, $sitedata->path)) {
        return false;
    }

    // Store values to save in user meta.
    $meta = [];

    $meta['bio'] = null;

    $meta['role'] = $role;

    $meta['status'] = (string) 'A';

    $meta['admin_layout'] = (int) 0;

    $meta['admin_sidebar'] = (int) 0;

    $meta['admin_skin'] = (string) 'skin-red';

    /**
     * Filters a user's meta values and keys immediately after the user is added
     * and before any user meta is inserted.
     *
     * @param array $meta {
     *     Default meta values and keys for the user.
     *
     *     @type string $bio            The user's bio.
     *     @type string $role           The user's role.
     *     @type string $status         The user's status.
     *     @type int    $admin_layout   The user's layout option.
     *     @type int    $admin_sidebar  The user's sidebar option.
     *     @type int    $admin_skin     The user's skin option.
     * }
     * @param $userdata User object.
     */
    $meta = Filter::getInstance()->applyFilter('add_user_usermeta', $meta, $userdata);

    // Make sure metadata doesn't already exist for this user.
    $prefix = $sitedata->key;
    if (empty(get_usermeta(userId: $userdata->id, key: $prefix . $meta['role'], single: true))) {
        // Update user meta.
        foreach ($meta as $key => $value) {
            update_usermeta(userId: $userdata->id, metaKey: $prefix . $key, value: $value);
        }
    }

    return $userdata->id;
}

/**
 * Insert a site into the database.
 *
 * Some of the `$sitedata` array fields have filters associated with the values. Exceptions are
 * 'site_owner', 'site_registered' and 'site_modified' The filters have the prefix 'pre_'
 * followed by the field name. An example using 'site_name' would have the filter called,
 * 'pre_site_name' that can be hooked into.
 *
 * @file App/Shared/Helpers/site.php
 * @param array|Site|ServerRequestInterface $sitedata {
 *      An array or Site array of user data arguments.
 *
 *      @type string $id Sites's ID. If supplied, the site will be updated.
 *      @type string $domain The site's domain.
 *      @type string $mapping Mapped domain to use for the site.
 *      @type string $name The site's name/title.
 *      @type string $path The site's path.
 *      @type string $owner The site's owner.
 *      @type string $status The site's status.
 *      @type string $registered Date the site registered. Format is 'Y-m-d H:i:s'.
 *      @type string $modified Date the site's record was updated. Format is 'Y-m-d H:i:s'.
 *  }
 *
 * @return string|Error  The newly created site's id or Error if the site could not
 *                       be created.
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
 * @throws DateInvalidTimeZoneException
 */
function cms_insert_site(array|ServerRequestInterface|Site $sitedata): Error|string
{
    if ($sitedata instanceof ServerRequestInterface) {
        $sitedata = $sitedata->getParsedBody();
    } elseif ($sitedata instanceof Site) {
        $sitedata = $sitedata->toArray();
    }

    // Are we updating or creating?
    if (!empty($sitedata['id'])) {
        $update = true;
        $siteId = SiteId::fromString($sitedata['id']);
        $siteKey = $sitedata['key'];

        /** @var Site $siteBefore */
        $siteBefore = get_site_by(field: 'id', value: $siteId->toNative());

        if (is_false__($siteBefore)) {
            return new SiteError(
                esc_html__(
                    string: 'The ID of this entity is invalid.',
                    domain: 'devflow'
                ),
                'invalid_id'
            );
        }
        $previousStatus = get_site_status($siteId->toNative());
        /**
         * Fires immediately before a site is inserted into the site document.
         *
         * @file App/Shared/Helpers/site.php
         * @param string $previousStatus Status of the site before it is created or updated.
         * @param string $siteId         The site's site_id.
         * @param bool   $update         Whether this is an existing site or a new site.
         */
        Action::getInstance()->doAction('site_previous_status', $previousStatus, $siteId->toNative(), $update);

        /**
         * Create new site object.
         */
        $site = new Site();
        $site->id = $siteId->toNative();
    } else {
        $update = false;
        $previousStatus = 'new';
        $siteId = new SiteId();
        $siteKey = generate_site_key();
        /**
         * Fires immediately before a site is inserted into the site document.
         *
         * @file App/Shared/Helpers/site.php
         * @param string $previousStatus Status of the site before it is created or updated.
         * @param string $siteId         The site's site_id.
         * @param bool   $update         Whether this is an existing site or a new site.
         */
        Action::getInstance()->doAction('site_previous_status', $previousStatus, $siteId->toNative(), $update);

        /**
         * Create new site object.
         */
        $site = new Site();
        $site->id = $siteId->toNative();
    }
    $site->key = $siteKey;

    /** @var RequestInterface $request */
    $request = app(RequestInterface::class);
    $host = $request->getUri()->getHost();

    $rawSiteDomain = isset($sitedata['subdomain']) ?
    trim(strtolower($sitedata['subdomain'])) . '.' . $host :
    trim(strtolower($sitedata['domain']));
    $sanitizedSiteDomain = Sanitizer::item($rawSiteDomain);
    /**
     * Filters a site's domain before the site is created or updated.
     *
     * @file App/Shared/Helpers/site.php
     * @param string $sanitizedSiteDomain Site domain after it has been sanitized
     * @param string $preSiteDomain The site's domain.
     */
    $preSiteDomain = Filter::getInstance()->applyFilter(
        'pre_site_domain',
        (string) $sanitizedSiteDomain,
        (string) $rawSiteDomain
    );

    $siteDomain = trim($preSiteDomain);

    // site_domain cannot be empty.
    if (empty($siteDomain)) {
        return new SiteError(
            esc_html__(
                string: 'Site domain cannot be empty.',
                domain: 'devflow'
            ),
            'empty_value',
        );
    }

    if (!$update && if_site_domain_exists($siteDomain)) {
        return new SiteError(
            esc_html__(
                string: 'Sorry, that site already exists!',
                domain: 'devflow'
            ),
            'duplicate',
        );
    }
    $site->domain = $siteDomain;

    $rawSiteMapping = $sitedata['mapping'] ?? '';
    $sanitizedSiteMapping = Sanitizer::item($rawSiteMapping);
    /**
     * Filters a site's mapped domain before the site is created or updated.
     *
     * @file App/Shared/Helpers/site.php
     * @param string $sanitizedSiteMapping Mapped domain after it has been sanitized
     * @param string $rawSiteMapping The site's mapping.
     */
    $siteMapping = Filter::getInstance()->applyFilter(
        'pre_site_mapping',
        (string) $sanitizedSiteMapping,
        (string) $rawSiteMapping
    );
    $site->mapping = $siteMapping;

    $rawSiteName = $sitedata['name'];
    $sanitizedSiteName = Sanitizer::item($rawSiteName);
    /**
     * Filters a site's name before the site is created or updated.
     *
     * @file App/Shared/Helpers/site.php
     * @param string $sanitizedSiteName Site name after it has been sanitized
     * @param string $rawSiteName The site's name.
     */
    $siteName = Filter::getInstance()->applyFilter(
        'pre_site_name',
        (string) $sanitizedSiteName,
        (string) $rawSiteName
    );
    $site->name = $siteName;

    if (isset($sitedata['slug'])) {
        /**
         * cms_unique_site_slug will take the original slug supplied and check
         * to make sure that it is unique. If not unique, it will make it unique
         * by adding a number at the end.
         */
        $siteSlug = cms_unique_site_slug($sitedata['slug'], $siteName, $siteId->toNative());
    } else {
        /**
         * For an update, don't modify the site_slug if it
         * wasn't supplied as an argument.
         */
        $siteSlug = $siteBefore->slug;
    }

    $rawSiteSlug = $siteSlug;
    $sanitizedSiteSlug = Sanitizer::item($rawSiteSlug);
    /**
     * Filters a site's slug before created/updated.
     *
     * @file App/Shared/Helpers/site.php
     * @param string $sanitizedSiteSlug Site slug after it has been sanitized
     * @param string $rawSiteSlug The site's slug.
     */
    $siteSlug = Filter::getInstance()->applyFilter(
        'pre_site_slug',
        (string) $sanitizedSiteSlug,
        (string) $rawSiteSlug
    );
    $site->slug = $siteSlug;

    $rawSitePath = $sitedata['path'];
    $sanitizedSitePath = Sanitizer::item($rawSitePath);
    /**
     * Filters a site's path before the site is created or updated.
     *
     * @file App/Shared/Helpers/site.php
     * @param string $sanitizedSitePath Site path after it has been sanitized
     * @param string $rawSitePath The site's path.
     */
    $sitePath = Filter::getInstance()->applyFilter(
        'pre_site_path',
        (string) $sanitizedSitePath,
        (string) $rawSitePath
    );
    $site->path = $sitePath;

    /*
     * If there is no update, just check for `email_exists`. If there is an update,
     * check if current email and new email are the same, or not, and check `email_exists`
     * accordingly.
     */
    if (
        (!$update ||
                (!empty($siteBefore) &&
                0 !==
                        strcasecmp($siteDomain . $sitePath, $siteBefore->domain . $siteBefore->path))) &&
        if_site_exists($siteDomain, $sitePath)
    ) {
        return new SiteError(
            esc_html__(
                string: 'Sorry, that site domain and path is already used.',
                domain: 'devflow'
            ),
            'duplicate',
        );
    }

    $siteOwner = !isset($sitedata['owner']) ? get_current_user_id() : $sitedata['owner'];
    $site->owner = $siteOwner;

    $rawSiteStatus = !isset($sitedata['status']) ? 'public' : $sitedata['status'];
    $sanitizedSiteStatus = Sanitizer::item($rawSiteStatus);
    /**
     * Filters a site's status before the site is created or updated.
     *
     * @file App/Shared/Helpers/site.php
     * @param string $sanitizedSiteStatus Site status after it has been sanitized
     * @param string $rawSiteStatus The site's status.
     */
    $siteStatus = Filter::getInstance()->applyFilter(
        'pre_site_status',
        (string) $sanitizedSiteStatus,
        (string) $rawSiteStatus
    );
    $site->status = $siteStatus;

    $siteRegistered = (new DateTime())->current(type: 'db');

    $siteModified = (new DateTime())->current(type: 'db');

    $compacted = [
        'id' => $siteId->toNative(),
        'key' => $siteKey,
        'name' => $siteName,
        'slug' => $siteSlug,
        'domain' => $siteDomain,
        'mapping' => $siteMapping,
        'path' => $sitePath,
        'owner' => $siteOwner,
        'status' => $siteStatus,
        'registered' => $siteRegistered,
        'modified' => $siteModified,
    ];

    $data = unslash($compacted);

    /**
     * Filters site data before the record is created or updated.
     *
     * @file App/Shared/Helpers/site.php
     * @param array    $data {
     *     Values and keys for the site.
     *
     *     @type object $siteId        The site's id
     *     @type string $siteKey       The site's key
     *     @type string $siteDomain    The site's domain
     *     @type string $siteMapping   The site's mapped domain.
     *     @type string $siteName      The site's name/title.
     *     @type string $siteSlug      The site's slug.
     *     @type string $sitePath      The site's path.
     *     @type string $siteOwner     The site's owner.
     *     @type string $siteStatus    The site's status.
     * }
     * @param bool     $update      Whether the site is being updated rather than created.
     * @param string|null $siteId   ID of the site to be updated, or NULL if the site is being created.
     */
    $data = Filter::getInstance()->applyFilter(
        'cms_pre_insert_site_data',
        $data,
        $update,
        $update ? $siteBefore->id : null
    );

    $resolver = new NativeCommandHandlerResolver(
        container: ContainerFactory::make(config: config('commandbus.container'))
    );
    $odin = new Odin(bus: new SynchronousCommandBus($resolver));

    if ($update) {
        $site->modified = $siteModified;

        try {
            $command = new UpdateSiteCommand([
                'siteId' => SiteId::fromString($site->id),
                'siteName' => new StringLiteral($site->name),
                'siteSlug' => new StringLiteral($site->slug),
                'siteDomain' => new StringLiteral($site->domain),
                'siteMapping' => new StringLiteral($site->mapping),
                'sitePath' => new StringLiteral($site->path),
                'siteOwner' => UserId::fromString($site->owner),
                'siteStatus' => new StringLiteral($site->status),
                'siteModified' => QubusDateTimeImmutable::createFromDate(
                    date('Y', strtotime($site->modified)),
                    date('m', strtotime($site->modified)),
                    date('d', strtotime($site->modified)),
                    new QubusDateTimeZone(Options::factory()->read(optionKey: 'site_timezone'))
                ),
            ]);

            $odin->execute($command);

        } catch (PDOException $e) {
            FileLoggerFactory::getLogger()->error(
                sprintf(
                    'SQLSTATE[%s]: %s',
                    $e->getCode(),
                    $e->getMessage()
                ),
                [
                    'Site Function' => 'cms_insert_site'
                ]
            );
        }
    } else {
        $site->registered = $siteRegistered;

        try {
            $command = new CreateSiteCommand([
                'siteId' => SiteId::fromString($site->id),
                'siteKey' => new StringLiteral($site->key),
                'siteName' => new StringLiteral($site->name),
                'siteSlug' => new StringLiteral($site->slug),
                'siteDomain' => new StringLiteral($site->domain),
                'siteMapping' => new StringLiteral($site->mapping),
                'sitePath' => new StringLiteral($site->path),
                'siteOwner' => UserId::fromString($site->owner),
                'siteStatus' => new StringLiteral($site->status),
                'siteRegistered' => QubusDateTimeImmutable::createFromDate(
                    date('Y', strtotime($site->registered)),
                    date('m', strtotime($site->registered)),
                    date('d', strtotime($site->registered)),
                    new QubusDateTimeZone(Options::factory()->read(optionKey: 'site_timezone'))
                ),
            ]);

            $odin->execute($command);

        } catch (
            PDOException |
            CommandCouldNotBeHandledException |
            UnresolvableCommandHandlerException |
            ReflectionException $e
        ) {
            FileLoggerFactory::getLogger()->error(
                sprintf(
                    'SQLSTATE[%s]: %s',
                    $e->getCode(),
                    $e->getMessage()
                ),
                [
                    'Site Function' => 'cms_insert_site'
                ]
            );
        }
    }

    /** @var Site $site */
    $site = get_site_by('id', $siteId->toNative());

    if ($update) {
        /**
         * Fires immediately after an existing site is updated.
         *
         * @file App/Shared/Helpers/site.php
         * @param string $siteId   Site ID.
         * @param Site $site       Site data object.
         */
        Action::getInstance()->doAction('update_site', $siteId->toNative(), $site);

        /** @var Site $siteAfter */
        $siteAfter = get_site_by('id', $siteId->toNative());

        /**
         * Action hook triggered after existing site has been updated.
         *
         * @file App/Shared/Helpers/site.php
         * @param string $siteId    Site id.
         * @param Site $siteAfter   Site object following the update.
         * @param Site $siteBefore  Site object before the update.
         */
        Action::getInstance()->doAction('site_updated', $siteId->toNative(), $siteAfter, $siteBefore);
    }

    /**
     * Fires immediately after a new site is saved.
     *
     * @file App/Shared/Helpers/site.php
     * @param string $siteId Site ID.
     * @param Site $site     Site object.
     * @param bool $update   Whether this is an existing site or a new site.
     */
    Action::getInstance()->doAction('save_site', $siteId->toNative(), $site, $update);

    /**
     * Action hook triggered after site has been saved.
     *
     * The dynamic portion of this hook, `$siteStatus`,
     * is the site's status.
     *
     * @file App/Shared/Helpers/site.php
     * @param string $siteId    The site's id.
     * @param Site $site       Site object.
     * @param bool $update     Whether this is an existing site or a new site.
     */
    Action::getInstance()->doAction("save_site_{$siteStatus}", $siteId->toNative(), $site, $update);

    /**
     * Action hook triggered after site has been saved.
     *
     * @file App/Shared/Helpers/site.php
     * @param string $siteId   The site's id.
     * @param Site $site       Site object.
     * @param bool $update     Whether this is an existing site or a new site.
     */
    Action::getInstance()->doAction('cms_after_insert_site_data', $siteId->toNative(), $site, $update);

    return $siteId->toNative();
}

/**
 * Update a site in the database.
 *
 * See cms_insert_site() For what fields can be set in $sitedata.
 *
 * @file App/Shared/Helpers/site.php
 * @param array|ServerRequestInterface|Site $sitedata An array of site data or a site object.
 * @return Error|string The updated site's id or Error if update failed.
 * @throws CommandCouldNotBeHandledException
 * @throws CommandPropertyNotFoundException
 * @throws ContainerExceptionInterface
 * @throws DateInvalidTimeZoneException
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 * @throws TypeException
 * @throws UnresolvableCommandHandlerException
 * @throws UnresolvableQueryHandlerException
 */
function cms_update_site(array|ServerRequestInterface|Site $sitedata): Error|string
{
    if ($sitedata instanceof ServerRequestInterface) {
        $sitedata = $sitedata->getParsedBody();
    } elseif ($sitedata instanceof Site) {
        $sitedata = $sitedata->toArray();
    }
    $dfdb = dfdb();

    $details = $dfdb->getRow(
        $dfdb->prepare(
            "SELECT site_owner FROM {$dfdb->basePrefix}site WHERE site_id = ?",
            [
                $sitedata['id']
            ]
        ),
        Database::ARRAY_A
    );
    if ($details['site_owner'] !== $sitedata['owner']) {
        $ownerChange = true;
        $previousOwner = $details['site_owner'];
    } else {
        $ownerChange = false;
    }

    $id = $sitedata['id'] ?? '';
    if ('' === $id) {
        return new SiteError(
            esc_html__(
                string: 'The ID of this entity is invalid.',
                domain: 'devflow'
            ),
            'invalid_id',
        );
    }

    $siteId = cms_insert_site($sitedata);

    /**
     * If the site admin has changed, delete usermeta data of the old admin
     * and add usermeta data for the new
     */
    if (!is_null__($siteId) && $ownerChange) {
        $metaKey = $sitedata['key'];
        $oldMeta = $dfdb->getResults(
            $dfdb->prepare(
                "SELECT meta_key, meta_value FROM {$dfdb->basePrefix}usermeta WHERE user_id = ? AND meta_key LIKE ?",
                [
                    $previousOwner,
                    "%$metaKey%"
                ]
            ),
            Database::ARRAY_A
        );
        foreach ($oldMeta as $meta) {
            delete_usermeta($previousOwner, $meta['meta_key'], $meta['meta_value']);
        }
        add_user_to_site($sitedata['owner'], $sitedata['id'], 'admin');
    }

    //cms()->obj['sitecache']->clean($siteId);

    return $siteId;
}

/**
 * Deletes a site.
 *
 * @file App/Shared/Helpers/site.php
 * @param string $siteId ID of site to delete.
 * @return string|Error Returns id of deleted site or Error.
 * @throws CommandCouldNotBeHandledException
 * @throws CommandPropertyNotFoundException
 * @throws Exception
 * @throws ReflectionException
 * @throws UnresolvableCommandHandlerException
 * @throws UnresolvableQueryHandlerException
 */
function cms_delete_site(string $siteId): Error|string
{
    $resolver = new NativeCommandHandlerResolver(
        container: ContainerFactory::make(config: config(key: 'commandbus.container'))
    );
    $odin = new Odin(bus: new SynchronousCommandBus($resolver));

    /** @var Site $oldSite */
    $oldSite = get_site_by('id', $siteId);

    if (is_false__($oldSite)) {
        return new SiteError(
            esc_html__(
                string: 'Site does not exist.',
                domain: 'devflow'
            ),
            code: 'not_found'
        );
    }

    /**
     * Action hook triggered before the site is deleted.
     *
     * @file App/Shared/Helpers/site.php
     * @param string $id      Site ID.
     * @param Site   $oldSite Data object of site to be deleted.
     */
    Action::getInstance()->doAction('delete_site', $siteId, $oldSite);

    try {
        $command = new DeleteSiteCommand([
            'siteId' => SiteId::fromString($siteId)
        ]);

        $odin->execute($command);
    } catch (PDOException $e) {
        FileLoggerFactory::getLogger()->error(
            sprintf(
                'SQLSTATE[%s]: %s',
                $e->getCode(),
                $e->getMessage()
            ),
            [
                'Site Function' => 'cms_delete_site'
            ]
        );
    }

    /**
     * Action hook triggered after the site is deleted.
     *
     * @file App/Shared/Helpers/site.php
     * @param string $id    Site ID.
     * @param Site $oldSite Site object that was deleted.
     */
    Action::getInstance()->doAction('deleted_site', $siteId, $oldSite);

    //ttcms()->obj['sitecache']->clean($oldSite);

    return $siteId;
}

/**
 * Delete site user.
 *
 * @file App/Shared/Helpers/site.php
 * @param string $userId The id of user to be deleted.
 * @param array $params User parameters (assign_id and role).
 * @return bool|Error Returns true if successful or returns error otherwise.
 * @throws CommandPropertyNotFoundException
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 * @throws SessionException
 * @throws TypeException
 * @throws UnresolvableQueryHandlerException
 * @throws \Exception
 */
function cms_delete_site_user(string $userId, array $params = []): Error|bool
{
    $dfdb = dfdb();

    if ('' === $userId) {
        return false;
    }

    /** @var User $user */
    $user = get_userdata($userId);

    if (is_false__($user)) {
        return false;
    }

    if ($user->role === 'super') {
        Codefy::$PHP->flash->error(
            esc_html__(
                string: 'You are not allowed to delete a super administrator account.',
                domain: 'devflow'
            )
        );
        return false;
    }

    $sites = get_owner_sites($userId);

    if (isset($params['assign_id']) && !is_null__($params['assign_id']) && 'null' !== $params['assign_id']) {
        /** @var User $assignUser */
        $assignUser = get_userdata($params['assign_id']);
        /**
         * Clean cache of the assigned user.
         */
        SimpleCacheObjectCacheFactory::make(namespace: 'users')->delete(key: md5($assignUser->id));
        /**
         * We need to reassign the site(s) to the selected user and create the
         * needed usermeta for the site.
         */
        if (!empty($sites)) {
            foreach ($sites as $site) {
                SimpleCacheObjectCacheFactory::make(namespace: 'sites')->delete(key: md5($site->id));

                add_user_to_site($params['assign_id'], $site->id, $params['role']);

                $params = array_merge(['site_id' => $site->id], $params);

                /**
                 * Sites will be reassigned before the user is deleted.
                 *
                 * @file App/Shared/Helpers/site.php
                 * @param string $userId  ID of user to be deleted.
                 * @param array $params   User and site parameters (assign_id, role and site_id).
                 */
                Action::getInstance()->doAction('reassign_sites', $userId, $params);
            }
        }
    } else {
        if (!empty($sites)) {

            try {
                $dfdb->qb()->transactional(function () use ($dfdb, $userId) {
                    $dfdb->qb()
                            ->table(tableName: $dfdb->basePrefix . 'site')
                            ->where(condition: 'site_owner = ?', parameters: $userId)
                            ->delete();
                });
            } catch (PDOException $e) {
                return new SiteError($e->getCode(), $e->getMessage());
            }

            foreach ($sites as $oldSite) {
                $site = new Site((array) $oldSite);
                SimpleCacheObjectCacheFactory::make(namespace: 'sites')->delete(key: md5($site->id));
                /**
                 * Action hook triggered after the site is deleted.
                 *
                 * @param string  $siteId Site ID.
                 * @param Site    $site   Site object that was deleted.
                 */
                Action::getInstance()->doAction('deleted_site', $site->id, $site);
            }
        }
    }

    /**
     * Action hook fires immediately before a user is deleted from the usermeta document.
     *
     * @file App/Shared/Helpers/site.php
     * @param string $userId ID of the user to delete.
     * @param array $params  User parameters (assign_id and role).
     */
    Action::getInstance()->doAction('delete_site_user', $userId, $params);

    /**
     * Finally delete the user and metadata.
     */
    try {
        $dfdb->qb()->transactional(function () use ($dfdb, $userId) {
            $dfdb->qb()
                ->table($dfdb->basePrefix . 'user')
                ->where(condition: 'user_id = ?', parameters: $userId)
                ->delete();
        });

        $meta = $dfdb->getResults(
            $dfdb->prepare(
                "SELECT meta_id FROM {$dfdb->basePrefix}usermeta WHERE user_id = ?",
                [
                    $userId
                ]
            ),
            Database::ARRAY_A
        );

        if ($meta) {
            foreach ($meta as $mid) {
                delete_usermeta_by_mid($mid['meta_id']);
            }
        }
    } catch (PDOException $e) {
        return new SiteError(sprintf('ERROR[%s]: %s', $e->getCode(), $e->getMessage()));
    }

    /**
     * Clear the cache of the deleted user.
     */
    SimpleCacheObjectCacheFactory::make(namespace: 'users')->delete(key: md5($user->id));

    /**
     * Action hook fires immediately after a user has been deleted from the usermeta document.
     *
     * @file App/Shared/Helpers/site.php
     * @param string $userId   ID of the user who was deleted.
     * @param array $params    User parameters (assign_id and role).
     */
    Action::getInstance()->doAction('deleted_site_user', $userId, $params);

    return true;
}

/**
 * Creates new tables and user meta for site admin after new site
 * is created.
 *
 * @access private Used when the action hook `save_site` is called.
 *
 * @file App/Shared/Helpers/site.php
 * @param string $siteId Site id of the newly created site.
 * @param Site $site Site object of newly created site.
 * @param bool $update Whether the site is being created or updated.
 * @return string|bool Returns the site id if successful or false on failure.
 * @throws CommandPropertyNotFoundException
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 * @throws TypeException
 * @throws UnresolvableQueryHandlerException
 */
function new_site_schema(string $siteId, Site $site, bool $update): bool|string
{
    $dfdb = dfdb();

    if ($update) {
        return false;
    }

    /** @var Site $site */
    $site = get_site_by('id', $siteId);

    if (!$site) {
        return false;
    }

    /** @var User $userdata */
    $userdata = get_userdata($site->owner);

    $apiKey = generate_unique_key(length: 20);
    $basePrefix = $dfdb->basePrefix;
    $sitePrefix = $site->key;

    $insertData = file_get_contents(resource_path(path: 'tpl/new_site_db_insert.tpl'));
    $insertData = str_replace('{ulid_1}', Ulid::generateAsString(), $insertData);
    $insertData = str_replace('{ulid_2}', Ulid::generateAsString(), $insertData);
    $insertData = str_replace('{ulid_3}', Ulid::generateAsString(), $insertData);
    $insertData = str_replace('{ulid_4}', Ulid::generateAsString(), $insertData);
    $insertData = str_replace('{ulid_5}', Ulid::generateAsString(), $insertData);
    $insertData = str_replace('{ulid_6}', Ulid::generateAsString(), $insertData);
    $insertData = str_replace('{ulid_7}', Ulid::generateAsString(), $insertData);
    $insertData = str_replace('{ulid_8}', Ulid::generateAsString(), $insertData);
    $insertData = str_replace('{ulid_9}', Ulid::generateAsString(), $insertData);
    $insertData = str_replace('{ulid_10}', Ulid::generateAsString(), $insertData);
    $insertData = str_replace('{ulid_11}', Ulid::generateAsString(), $insertData);
    $insertData = str_replace('{ulid_12}', Ulid::generateAsString(), $insertData);
    $insertData = str_replace('{timezone}', config(key: 'app.timezone'), $insertData);
    $insertData = str_replace('{site_prefix}', $sitePrefix, $insertData);
    $insertData = str_replace('{base_prefix}', $basePrefix, $insertData);
    $insertData = str_replace('{sitename}', $site->name, $insertData);
    $insertData = str_replace('{admin_email}', $userdata->email, $insertData);
    $insertData = str_replace('{api_key}', $apiKey, $insertData);

    try {
        $dfdb->qb()->getConnection()->getPDO()->exec($insertData);
    } catch (PDOException $e) {
        FileLoggerFactory::getLogger()->error(
            sprintf(
                'SQLSTATE[new_site]: %s',
                $e->getMessage()
            ),
            [
                'Site Function' => 'new_site_schema'
            ]
        );
    }
    // Store values to save in user meta.
    $meta = [];
    $meta['bio'] = null;
    $meta['role'] = 'admin';
    $meta['status'] = (string) 'A';
    $meta['admin_layout'] = (int) 0;
    $meta['admin_sidebar'] = (int) 0;
    $meta['admin_skin'] = (string) 'skin-red';
    /**
     * Filters a user's meta values and keys immediately after the user is added
     * and before any user meta is inserted.
     *
     * @file App/Shared/Helpers/site.php
     * @param array $meta {
     *     Default meta values and keys for the user.
     *
     *     @type string $bio            The user's bio.
     *     @type string $role           The user's role.
     *     @type string $status         The user's status.
     *     @type int    $admin_layout   The user's layout option.
     *     @type int    $admin_sidebar  The user's sidebar option.
     *     @type int    $admin_skin     The user's skin option.
     * }
     * @param object $userdata   User object.
     */
    $meta = Filter::getInstance()->applyFilter('new_site_usermeta', $meta, $userdata);
    // Update user meta.
    foreach ($meta as $key => $value) {
        update_usermeta($userdata->id, $sitePrefix . $key, $value);
    }
    return $site->id;
}

/**
 * Adds status label for site's table.
 *
 * @file App/Shared/Helpers/site.php
 * @param string $status Status to check for.
 * @return string Site's status.
 * @throws Exception
 * @throws ReflectionException
 */
function cms_site_status_label(string $status): string
{
    $label = [
        'public' => 'label-success',
        'archive' => 'label-danger'
    ];

    /**
     * Filters the label result.
     */
    return Filter::getInstance()->applyFilter('site_status_label', $label[$status], $status);
}

/**
 * Checks if site exists or is archived.
 *
 * @access private
 *
 * @file App/Shared/Helpers/site.php
 * @return ResponseInterface
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 * @throws \Exception
 */
function does_site_exist(): \Psr\Http\Message\ResponseInterface
{
    $dfdb = dfdb();

    $baseUrl = site_url();
    $sitePath = str_replace('index.php', '', app(ServerRequestInterface::class)['PHP_SELF']);
    $siteDomain = str_replace(['http://', 'https://', $sitePath], '', $baseUrl);

    $site = $dfdb->getCol(
        $dfdb->prepare(
            "SELECT site_status FROM {$dfdb->basePrefix}site WHERE site_domain = ? AND site_path = ?",
            [
                $siteDomain,
                $sitePath
            ]
        )
    );

    if (is_null__($site)) {
        return JsonResponseFactory::create(data: 'Not found.', status: 404);
    }

    if (esc_html($site['site_status']) === 'archive') {
        return JsonResponseFactory::create(data: 'Site unavailable.', status: 503);
    }

    return JsonResponseFactory::create(data: 'Not found.', status: 404);
}

/**
 * A function which retrieves cms site name.
 *
 * Purpose of this function is for the `site_name`
 * filter.
 *
 * @file App/Shared/Helpers/site.php
 * @param string $siteId
 * @return string Site's name on success or '' on failure.
 * @throws CommandPropertyNotFoundException
 * @throws ReflectionException
 * @throws UnresolvableQueryHandlerException|Exception
 */
function get_site_name(string $siteId): string
{
    /** @var Site $site */
    $site = get_site_by('id', $siteId);

    if (!$site) {
        return '';
    }

    $name = $site->name;
    /**
     * Filters the site name.
     *
     * @file App/Shared/Helpers/site.php
     * @param string    $name The site's name.
     * @param string    $siteId The site ID.
     */
    return Filter::getInstance()->applyFilter('site_name', $name, $siteId);
}

/**
 * A function which retrieves cms site domain.
 *
 * Purpose of this function is for the `site_domain`
 * filter.
 *
 * @file App/Shared/Helpers/site.php
 * @param string $siteId The unique id of a site.
 * @return string Site's domain on success or '' on failure.
 * @throws CommandPropertyNotFoundException
 * @throws Exception
 * @throws ReflectionException
 * @throws UnresolvableQueryHandlerException
 */
function get_site_domain(string $siteId): string
{
    /** @var Site $site */
    $site = get_site_by('id', $siteId);

    if (!$site) {
        return '';
    }

    $domain = $site->domain;
    /**
     * Filters the site domain.
     *
     * @file App/Shared/Helpers/site.php
     * @param string    $domain The site's domain.
     * @param string    $siteId The site ID.
     */
    return Filter::getInstance()->applyFilter('site_domain', $domain, $siteId);
}

/**
 * A function which retrieves cms site path.
 *
 * Purpose of this function is for the `site_path`
 * filter.
 *
 * @file App/Shared/Helpers/site.php
 * @param string $siteId The unique id of a site.
 * @return string Site's path on success or false on failure.
 * @throws CommandPropertyNotFoundException
 * @throws Exception
 * @throws ReflectionException
 * @throws UnresolvableQueryHandlerException
 */
function get_site_path(string $siteId): string
{
    /** @var Site $site */
    $site = get_site_by('id', $siteId);

    if (!$site) {
        return '';
    }

    $path = $site->path;
    /**
     * Filters the site path.
     *
     * @file App/Shared/Helpers/site.php
     * @param string    $path The site's path.
     * @param string    $siteId The site ID.
     */
    return Filter::getInstance()->applyFilter('site_path', $path, $siteId);
}

/**
 * A function which retrieves cms site owner.
 *
 * Purpose of this function is for the `site_owner`
 * filter.
 *
 * @file App/Shared/Helpers/site.php
 * @param string $siteId The unique id of a site.
 * @return string Site's owner on success or false on failure.
 * @throws CommandPropertyNotFoundException
 * @throws Exception
 * @throws ReflectionException
 * @throws UnresolvableQueryHandlerException
 */
function get_site_owner(string $siteId): string
{
    /** @var Site $site */
    $site = get_site_by('id', $siteId);

    if (!$site) {
        return '';
    }

    $owner = $site->owner;
    /**
     * Filters the site owner.
     *
     * @file App/Shared/Helpers/site.php
     * @param string    $owner The site's owner.
     * @param string    $siteId The site ID.
     */
    return Filter::getInstance()->applyFilter('site_owner', $owner, $siteId);
}

/**
 * A function which retrieves cms site status.
 *
 * Purpose of this function is for the `site_status`
 * filter.
 *
 * @file App/Shared/Helpers/site.php
 * @param string $siteId The unique id of a site.
 * @return string Site's status on success or false on failure.
 * @throws CommandPropertyNotFoundException
 * @throws Exception
 * @throws ReflectionException
 * @throws UnresolvableQueryHandlerException
 */
function get_site_status(string $siteId): string
{
    /** @var Site $site */
    $site = get_site_by('id', $siteId);

    if (!$site) {
        return '';
    }

    $status = $site->status;
    /**
     * Filters the site status.
     *
     * @file App/Shared/Helpers/site.php
     * @param string    $status The site's status.
     * @param string    $siteId The site ID.
     */
    return Filter::getInstance()->applyFilter('site_status', $status, $siteId);
}

/**
 * Creates a unique site slug.
 *
 * @file App/Shared/Helpers/site.php
 * @param string $originalSlug Original slug of site.
 * @param string $originalTitle Original title of site.
 * @param string $siteId Unique site id.
 * @return string Unique site slug.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function cms_unique_site_slug(string $originalSlug, string $originalTitle, string $siteId): string
{
    if (if_site_slug_exists($siteId, $originalSlug)) {
        $siteSlug = cms_slugify($originalTitle, 'site');
    } else {
        $siteSlug = $originalSlug;
    }
    /**
     * Filters the unique site slug before returned.
     *
     * @file App/Shared/Helpers/site.php
     * @param string $siteSlug      Unique site slug.
     * @param string $originalSlug  The site's original slug.
     * @param string $originalTitle The site's original title before slugified.
     * @param string $siteId        The site's unique id.
     */
    return Filter::getInstance()->applyFilter(
        'cms_unique_site_slug',
        $siteSlug,
        $originalSlug,
        $originalTitle,
        $siteId
    );
}

/**
 * Retrieves raw info about current site.
 *
 * @file App/Shared/Helpers/site.php
 * @param string $show
 * @param string $filter
 * @return string
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_siteinfo(string $show = '', string $filter = 'raw'): string
{
    $dispatch = [
        'homeurl' => home_url(),
        'siteurl' => site_url(),
        'description' => Options::factory()->read(optionKey: 'site_description'),
        'sitename' => Options::factory()->read(optionKey: 'sitename'),
        'timezone' => Options::factory()->read(optionKey: 'site_timezone'),
        'admin_email' => Options::factory()->read(optionKey: 'admin_email'),
        'locale' => Options::factory()->read(optionKey: 'site_locale'),
        'release' => Devflow::inst()->release(),
    ];

    $output = $show === '' ? $dispatch['sitename'] : $dispatch[$show];

    $url = true;
    if (
        !str_contains($show, 'url') &&
        !str_contains($show, 'home')
    ) {
        $url = false;
    }

    if ('display' === $filter) {
        if ($url) {
            /**
             * Filters the URL returned by get_siteinfo().
             *
             * @file App/Shared/Helpers/site.php
             * @param mixed $output The URL returned by siteinfo().
             * @param mixed $show   Type of information requested.
             */
            $output = Filter::getInstance()->applyFilter('siteinfo_url', $output, $show);
        } else {
            /**
             * Filters the site information returned by get_siteinfo().
             *
             * @file App/Shared/Helpers/site.php
             * @param mixed $output The requested non-URL site information.
             * @param mixed $show   Type of information requested.
             */
            $output = Filter::getInstance()->applyFilter('siteinfo', $output, $show);
        }
    }

    return $output;
}

/**
 * Retrieves filtered info about current site.
 *
 * @file App/Shared/Helpers/site.php
 * @param string $show
 * @return string
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function siteinfo(string $show = ''): string
{
    return get_siteinfo($show, 'display');
}

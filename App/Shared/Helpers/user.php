<?php

declare(strict_types=1);

namespace App\Shared\Helpers;

use App\Application\Devflow;
use App\Domain\User\Command\UpdateUserPasswordCommand;
use App\Domain\User\Command\CreateUserCommand;
use App\Domain\User\Command\DeleteUserCommand;
use App\Domain\User\Command\UpdateUserCommand;
use App\Domain\User\Model\User;
use App\Domain\User\Query\FindUsersQuery;
use App\Domain\User\UserError;
use App\Domain\User\ValueObject\UserId;
use App\Domain\User\ValueObject\Username;
use App\Domain\User\ValueObject\UserToken;
use App\Infrastructure\Persistence\Cache\UserCachePsr16;
use App\Infrastructure\Persistence\Database;
use App\Infrastructure\Services\NativePhpCookies;
use App\Infrastructure\Services\Options;
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
use Codefy\Framework\Support\Password;
use Codefy\QueryBus\Busses\SynchronousQueryBus;
use Codefy\QueryBus\Enquire;
use Codefy\QueryBus\Resolvers\NativeQueryHandlerResolver;
use Codefy\QueryBus\UnresolvableQueryHandlerException;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Exception\BadFormatException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Key;
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
use Qubus\Http\Session\SessionException;
use Qubus\NoSql\Collection;
use Qubus\NoSql\Exceptions\InvalidJsonException;
use Qubus\Support\DateTime\QubusDateTimeImmutable;
use Qubus\ValueObjects\StringLiteral\StringLiteral;
use Qubus\ValueObjects\Web\EmailAddress;
use ReflectionException;

use function array_map;
use function Codefy\Framework\Helpers\config;
use function Codefy\Framework\Helpers\mail;
use function Codefy\Framework\Helpers\storage_path;
use function in_array;
use function Qubus\Security\Helpers\esc_html;
use function Qubus\Security\Helpers\esc_html__;
use function Qubus\Security\Helpers\t__;
use function Qubus\Security\Helpers\trim__;
use function Qubus\Security\Helpers\unslash;
use function Qubus\Support\Helpers\concat_ws;
use function Qubus\Support\Helpers\is_false__;
use function Qubus\Support\Helpers\is_null__;
use function sprintf;
use function strcasecmp;
use function strtolower;

/**
 * Retrieves all users.
 *
 * @file App/Shared/Helpers/user.php
 * @return mixed
 * @throws ReflectionException
 * @throws UnresolvableQueryHandlerException
 */
function get_all_users(): mixed
{
    $resolver = new NativeQueryHandlerResolver(
        container: ContainerFactory::make(config: config(key: 'querybus.aliases'))
    );
    $enquirer = new Enquire(bus: new SynchronousQueryBus($resolver));

    $query = new FindUsersQuery();

    return $enquirer->execute($query);
}

/**
 * Print a dropdown list of users.
 *
 * @file App/Shared/Helpers/user.php
 * @param string|null $active If working with active record, it will be the user's id.
 * @return void Dropdown list of users.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_users_dropdown(?string $active = null): void
{
    $dfdb = dfdb();

    $sql = "SELECT DISTINCT user_id FROM {$dfdb->basePrefix}user WHERE user_id NOT IN(?)";

    $users = $dfdb->getResults(query: $dfdb->prepare($sql, [$active]), output: Database::ARRAY_A);

    foreach ($users as $user) {
        echo '<option value="' . esc_html($user['user_id'])
        . '"' . selected(esc_html($user['user_id']), $active, false) . '>'
        . get_name(esc_html($user['user_id'])) . '</option>';
    }
}

/**
 * Get the current user's ID
 *
 * @file App/Shared/Helpers/user.php
 * @return string The current user's ID, or '' if no user is logged in.
 */
function get_current_user_id(): string
{
    $verify = NativePhpCookies::factory()->verifySecureCookie(key: 'USERCOOKIEID');
    if (is_false__($verify)) {
        return '';
    }
    $cookie = get_secure_cookie_data(key: 'USERCOOKIEID');
    if (is_false__($cookie)) {
        return '';
    }

    return $cookie->id;
}

/**
 * Returns object of data for current user.
 *
 * @file App/Shared/Helpers/user.php
 * @return object|false
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_current_user(): false|object
{
    return get_userdata(get_current_user_id());
}

/**
 * Retrieve user info by a given field from the user's table.
 *
 * @file App/Shared/Helpers/user.php
 * @param string $field The field to retrieve the user with.
 * @param int|string $value A value for $field (id, login or token).
 * @return User|false User array on success, false otherwise.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_user_by(string $field, mixed $value): User|false
{
    $userdata = (new User(dfdb()))->findBy($field, $value);

    if (is_false__($userdata)) {
        return false;
    }

    return $userdata;
}

/**
 * Retrieve user info by user_id.
 *
 * @file App/Shared/Helpers/user.php
 * @param mixed $userId User's id.
 * @return object|false User array on success, false on failure.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_userdata(string $userId): false|object
{
    return get_user_by(field: 'id', value: $userId);
}

/**
 * Returns the name of a particular user.
 *
 * Uses `get_name` filter.
 *
 * @file App/Shared/Helpers/user.php
 * @param string $id User ID.
 * @param bool $reverse Reverse order (true = Last Name, First Name).
 * @return string User's name.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_name(string $id, bool $reverse = false): string
{
    /** @var User $name */
    $name = get_user_by(field: 'id', value: $id);

    if ($reverse) {
        $_name = $name->fname . ' ' . $name->lname;
    } else {
        $_name = $name->lname . ', ' . $name->fname;
    }

    return Filter::getInstance()->applyFilter('get_name', $_name);
}

/**
 * Shows selected user's initials instead of
 * his/her full name.
 *
 * Uses `get_initials` filter.
 *
 * @file App/Shared/Helpers/user.php
 * @param string $id User ID
 * @param int $initials Number of initials to show.
 * @return string User's initials.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_initials(string $id, int $initials = 2): string
{
    /** @var User $name */
    $name = get_user_by(field: 'id', value: $id);

    if ($initials === 2) {
        $_initials = mb_substr($name->fname, 0, 1, 'UTF-8')
        . '. ' . mb_substr($name->lname, 0, 1, 'UTF-8') . '.';
    } else {
        $_initials = $name->lname . ', ' . mb_substr($name->fname, 0, 1, 'UTF-8') . '.';
    }

    return Filter::getInstance()->applyFilter('get_initials', $_initials);
}

/**
 * Retrieve requested field from user meta table based on user's id.
 *
 * @file App/Shared/Helpers/user.php
 * @param string $id User ID.
 * @param string $field Data requested of particular user.
 * @return mixed
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_user_value(string $id, string $field): mixed
{
    $value = get_user_by(field: 'id', value: $id);
    return $value->{$field};
}

/**
 * Checks whether the given username exists.
 *
 * Uses `username_exists` filter.
 *
 * @file App/Shared/Helpers/user.php
 * @param string $username Username to check.
 * @return string|false The user's ID on success or false on failure.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function username_exists(string $username): false|string
{
    /** @var User $user */
    $user = get_user_by(field: 'login', value: $username);
    if (is_false__($user)) {
        return false;
    }

    $userId = $user->id;

    /**
     * Filters whether the given username exists or not.
     *
     * @file App/Shared/Helpers/user.php
     * @param string|false $userId  The user's user_id on success or false on failure.
     * @param string    $username   Username to check.
     */
    return Filter::getInstance()->applyFilter('username_exists', $userId, $username);
}

/**
 * Checks whether the given email exists.
 *
 * Uses `email_exists` filter.
 *
 * @file App/Shared/Helpers/user.php
 * @param string $email Email to check.
 * @return string|false The user's ID on success or false on failure.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function email_exists(string $email): false|string
{
    $dfdb = dfdb();
    $prepare = "SELECT * FROM {$dfdb->basePrefix}user WHERE user_email = ?";

    if ($user = $dfdb->getRow($dfdb->prepare($prepare, [$email]))) {
        $userId = $user->user_id;
    } else {
        $userId = false;
    }

    /**
     * Filters whether the given email exists or not.
     *
     * @file App/Shared/Helpers/user.php
     * @param string|false $userId The user's user_id on success, and false on failure.
     * @param string       $email  Email to check.
     */
    return Filter::getInstance()->applyFilter('email_exists', $userId, $email);
}

/**
 * Adds label to user's status.
 *
 * @file App/Shared/Helpers/user.php
 * @param string $status
 * @return string User's status
 * @throws Exception
 * @throws ReflectionException
 */
function user_status_label(string $status): string
{
    $label = [
        'A' => 'label-success',
        'I' => 'label-danger'
    ];

    /**
     * Filters the label result.
     *
     * @param array $label User's label.
     */
    return Filter::getInstance()->applyFilter('user_status_label', $label[$status], $status);
}

/**
 * Retrieve a list of system defined user roles.
 *
 * @file App/Shared/Helpers/user.php
 * @param string|null $active
 * @return void
 */
function get_system_roles(?string $active = null): void
{
    $roles = config(key: 'rbac.roles');

    foreach ($roles as $role => $permission) {
        echo '<option value="' . esc_html($role) . '"' . selected($active, esc_html($role), false) . '>' .
        esc_html($role) .
        '</option>';
    }
}

/**
 * Retrieve a list of all users as dropdown options.
 *
 * @file App/Shared/Helpers/user.php
 * @param string|null $active
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 * @throws UnresolvableQueryHandlerException
 */
function get_users_dropdown_list(?string $active = null): void
{
    $resolver = new NativeQueryHandlerResolver(
        container: ContainerFactory::make(config: config(key: 'querybus.aliases'))
    );
    $enquirer = new Enquire(bus: new SynchronousQueryBus($resolver));

    $query = new FindUsersQuery();
    $users = $enquirer->execute($query);

    foreach ($users as $user) {
        echo '<option value="' . $user['id'] . '"' . selected($active, $user['id'], false) . '>'
        . get_name($user['id']) . '</option>';
    }
}

/**
 * Retrieve user meta field for a user.
 *
 * @file App/Shared/Helpers/user.php
 * @param string $userId User ID.
 * @param string $key Optional. The meta key to retrieve. By default, returns data for all keys.
 * @param bool $single Whether to return a single value.
 * @return array|string Will be an array if $single is false. Will be value of meta_value field if $single is true.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_usermeta(string $userId, string $key = '', bool $single = false): array|string
{
    return MetaData::factory(dfdb()->prefix . 'usermeta')
            ->read('user', $userId, $key, $single);
}

/**
 * Get user meta data by entity ID.
 *
 * @file App/Shared/Helpers/user.php
 * @param string $mid
 * @return array|bool
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_usermeta_by_mid(string $mid): bool|array
{
    return MetaData::factory(dfdb()->prefix . 'usermeta')
            ->readByMid('user', $mid);
}

/**
 * Update user meta based on user ID.
 *
 * Use the $prevValue parameter to differentiate between meta fields with the
 * same key and user ID.
 *
 * If the meta for the user does not exist, it will be added.
 *
 * @file App/Shared/Helpers/user.php
 * @param string $userId User ID.
 * @param string $metaKey Metadata key.
 * @param mixed $value Metadata value.
 * @param mixed $prevValue Optional. Previous value to check before removing.
 * @return bool|string Meta ID if the key didn't exist, true on successful update, false on failure.
 * @throws CommandPropertyNotFoundException
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 * @throws TypeException
 * @throws UnresolvableQueryHandlerException
 */
function update_usermeta(string $userId, string $metaKey, mixed $value, mixed $prevValue = ''): bool|string
{
    return MetaData::factory(dfdb()->prefix . 'usermeta')
            ->update('user', $userId, $metaKey, $value, $prevValue);
}

/**
 * Update user meta by entity ID.
 *
 * @file App/Shared/Helpers/user.php
 * @param string $mid
 * @param string $metaKey
 * @param mixed $value
 * @return bool
 * @throws CommandPropertyNotFoundException
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 * @throws TypeException
 * @throws UnresolvableQueryHandlerException
 */
function update_usermeta_by_mid(string $mid, string $metaKey, mixed $value): bool
{
    return MetaData::factory(dfdb()->prefix . 'usermeta')
            ->updateByMid('user', $mid, $metaKey, $value);
}

/**
 * Adds meta data to a user.
 *
 * @file App/Shared/Helpers/user.php
 * @param string $userId User ID.
 * @param string $meta Metadata name.
 * @param mixed $value Metadata value.
 * @param bool $unique Optional. Whether the same key should not be added. Default false.
 * @return string|false Meta ID on success, false on failure.
 * @throws CommandPropertyNotFoundException
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 * @throws TypeException
 * @throws UnresolvableQueryHandlerException
 */
function add_usermeta(string $userId, string $meta, mixed $value, bool $unique = false): false|string
{
    return MetaData::factory(dfdb()->prefix . 'usermeta')
            ->create('user', $userId, $meta, $value, $unique);
}

/**
 * Remove meta matching criteria from a user.
 *
 * You can match based on the key, or key and value. Removing based on key and
 * value, will keep from removing duplicate meta with the same key. It also
 * allows removing all meta matching key, if needed.
 *
 * @file App/Shared/Helpers/user.php
 * @param string $userId User ID
 * @param string $meta Metadata name.
 * @param mixed $value Optional. Metadata value.
 * @return bool True on success, false on failure.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function delete_usermeta(string $userId, string $meta, mixed $value = ''): bool
{
    return MetaData::factory(dfdb()->prefix . 'usermeta')
            ->delete('user', $userId, $meta, $value);
}

/**
 * Delete user meta data by entity ID.
 *
 * @file App/Shared/Helpers/user.php
 * @param string $mid
 * @return bool
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function delete_usermeta_by_mid(string $mid): bool
{
    return MetaData::factory(dfdb()->prefix . 'usermeta')
            ->deleteByMid('user', $mid);
}

/**
 * Retrieve user option that can be either per Site or global.
 *
 * If the user ID is not given, then the current user will be used instead. If
 * the user ID is given, then the user data will be retrieved. The filter for
 * the result, will also pass the original option name and finally the user data
 * object as the third parameter.
 *
 * The option will first check for the per site name and then the global name.
 *
 * Uses `get_user_option_$option` filter.
 *
 * @file App/Shared/Helpers/user.php
 * @param string $option User option name.
 * @param string $userId User ID.
 * @return string|false User option value on success or false on failure.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_user_option(string $option, string $userId = ''): false|string
{
    if (empty($userId)) {
        $userId = get_current_user_id();
    }

    /** @var User $user */
    if (!$user = get_userdata($userId)) {
        return false;
    }

    $prefix = dfdb()->prefix;

    if ($user->isSet(key: $prefix . $option)) {
        $result = $user->get(key: $prefix . $option);
    } elseif ($user->isSet(key: $option)) {
        $result = $user->get(key: $option);
    } elseif ('' !== get_usermeta($userId, $option, true)) {
        $result = get_usermeta($userId, $option, true);
    } elseif ('' !== get_usermeta($userId, $prefix . $option, true)) {
        $result = get_usermeta($userId, $prefix . $option, true);
    } else {
        return false;
    }

    /**
     * Filters a specific user option value.
     *
     * The dynamic portion of the hook name, `$option`, refers to the user option name.
     *
     * @file App/Shared/Helpers/user.php
     * @param string|false  $result Value for the user's option.
     * @param string $option Name of the option being retrieved.
     * @param string $userId ID of the user whose option is being retrieved.
     */
    return Filter::getInstance()->applyFilter("get_user_option_{$option}", $result, $option, $userId);
}

/**
 * Update user option with global site capability.
 *
 * User options are just like user metadata except that they have support for
 * global site options. If the 'global' parameter is false, which it is by default
 * it will prepend the TriTan CMS table prefix to the option name.
 *
 * Deletes the user option if $newvalue is empty.
 *
 * @file App/Shared/Helpers/user.php
 * @param string $userId User ID.
 * @param string $optionName User option name.
 * @param mixed $newvalue User option value.
 * @param bool $global Optional. Whether option name is global or site specific.
 *                     Default false (site specific).
 * @return bool|int|string User meta ID if the option didn't exist, true on successful update,
 *                         false on failure.
 * @throws CommandPropertyNotFoundException
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 * @throws TypeException
 * @throws UnresolvableQueryHandlerException
 */
function update_user_option(string $userId, string $optionName, mixed $newvalue, bool $global = false): bool|int|string
{
    if (!$global) {
        $optionName = dfdb()->prefix . $optionName;
    }

    return update_usermeta($userId, $optionName, $newvalue);
}

/**
 * Delete user option with global site capability.
 *
 * User options are just like user metadata except that they have support for
 * global site options. If the 'global' parameter is false, which it is by default
 * it will prepend the TriTan CMS table prefix to the option name.
 *
 * @file App/Shared/Helpers/user.php
 * @param string $userId User ID
 * @param string $optionName User option name.
 * @param bool $global Optional. Whether option name is global or site specific.
 *                     Default false (site specific).
 * @return bool True on success or false on failure.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function delete_user_option(string $userId, string $optionName, bool $global = false): bool
{
    if (!$global) {
        $optionName = dfdb()->prefix . $optionName;
    }

    return delete_usermeta($userId, $optionName);
}

/**
 * Insert a user into the database.
 *
 * @file App/Shared/Helpers/user.php
 * @param array|ServerRequestInterface|User $userdata An array, object or User object of user data arguments.
 *
 *  {
 *      @type string $id User's ID. If supplied, the user will be updated.
 *      @type string $pass The plain-text user password.
 *      @type string $login The user's login username.
 *      @type string $fname The user's first name.
 *      @type string $mname The user's middle name.
 *      @type string $lname The user's last name.
 *      @type string $bio The user's biographical description.
 *      @type string $email The user's email address.
 *      @type string $url The user's url.
 *      @type string $status The user's status.
 *      @type int $admin_layout The user's admin layout option.
 *      @type int $admin_sidebar The user's admin sidebar option
 *      @type string $admin_skin The user's admin skin option.
 *      @type string $registered Date the user registered. Format is 'Y-m-d H:i:s'.
 *      @type string $modified Date the user's account was updated. Format is 'Y-m-d H:i:s'.
 *  }
 *
 * @return string|Error The newly created user's user_id or Error if user could not be created.
 * @throws CommandPropertyNotFoundException
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 * @throws TypeException
 * @throws UnresolvableQueryHandlerException
 */
function cms_insert_user(array|ServerRequestInterface|User $userdata): string|Error
{
    if ($userdata instanceof ServerRequestInterface) {
        $userdata = $userdata->getParsedBody();
    } elseif ($userdata instanceof User) {
        $userdata = $userdata->toArray();
    }

    $defaults = [
        'url' => '',
        'bio' => '',
        'timezone' => config('app.timezone'),
        'dateFormat' => 'd F Y',
        'timeFormat' => 'h:i A',
        'locale' => config('app.locale'),
    ];

    $userdata = Utils::parseArgs($userdata, $defaults);

    // Are we updating or creating?
    if (!empty($userdata['id']) && !is_false__(get_user_by('id', $userdata['id']))) {
        $update = true;
        $userId = UserId::fromString($userdata['id']);
        $userToken = $userdata['token'];
        /** @var User $oldUserData */
        $oldUserData = get_userdata($userId->toNative());

        if (!$oldUserData) {
            return new UserError(message: esc_html__(string: 'Invalid user id.', domain: 'devflow'));
        }

        // hashed in cms_update_user(), plaintext if called directly
        $userPass = !empty($userdata['pass']) ? $userdata['pass'] : $oldUserData['pass'];

        /**
         * Create a new user object.
         */
        $user = new User();
        $user->id = $userId->toNative();
        $user->token = $userToken;
        $user->pass = $userPass;
        $user->role = $userdata['role'];
    } else {
        $update = false;
        $userId = new UserId();
        /**
         * Hash the plaintext password.
         *
         * @param string $userPass Hashed password.
         */
        $userPass = Password::hash($userdata['pass']);

        /**
         * Create new User object.
         */
        $user = new User();
        $user->id = $userId->toNative();
        $user->token = UserToken::generateAsString();
        $user->pass = $userPass;
        $user->role = $userdata['role'];
    }

    // Store values to save in user meta.
    $meta = [];

    //Remove any non-printable chars from the login string to see if we have ended up with an empty username
    $rawUserLogin = $userdata['login'];
    $sanitizedUserLogin = $user->login = Sanitizer::username($rawUserLogin, true);
    /**
     * Filters a username after it has been sanitized.
     *
     * This filter is called before the user is created or updated.
     *
     * @file App/Shared/Helpers/user.php
     * @param string $sanitizedUserLogin Username after it has been sanitized.
     * @param string $rawUserLogin The user's login.
     */
    $preUserLogin = Filter::getInstance()->applyFilter(
        'pre_user_login',
        (string) $sanitizedUserLogin,
        (string) $rawUserLogin
    );

    //Remove any non-printable chars from the login string to see if we have ended up with an empty username
    $userLogin = trim__($preUserLogin);

    // userLogin must be between 3 and 60 characters.
    if (empty($userLogin)) {
        return new UserError(message: esc_html__(
            string: 'Cannot create a user with an empty username.',
            domain: 'devflow'
        ));
    } elseif (mb_strlen($userLogin) < 3) {
        return new UserError(message: esc_html__(
            string: 'Username must be at least 3 characters long.',
            domain: 'devflow'
        ));
    } elseif (mb_strlen($userLogin) > 60) {
        return new UserError(message: esc_html__(
            string: 'Username may not be longer than 60 characters.',
            domain: 'devflow'
        ));
    }

    if (!$update && username_exists($userLogin)) {
        return new UserError(message: esc_html__(string: 'Sorry, that username cannot be used.', domain: 'devflow'));
    }

    /**
     * Filters the list of blacklisted usernames.
     *
     * @file App/Shared/Helpers/user.php
     * @param array $usernames Array of blacklisted usernames.
     */
    $illegalLogins = (array) Filter::getInstance()->applyFilter('illegal_user_logins', blacklisted_usernames());

    if (in_array(strtolower($userLogin), array_map('\strtolower', $illegalLogins))) {
        return new UserError(
            message: sprintf(
                t__(
                    msgid: 'Sorry, the username <strong>%s</strong> is not allowed.',
                    domain: 'devflow'
                ),
                $userLogin
            )
        );
    }

    $userUrl = $userdata['url'];

    $rawUserEmail = $userdata['email'];
    $sanitizedUserEmail = $user->email = Sanitizer::item($rawUserEmail, 'email');
    /**
     * Filters a user's email before the user is created or updated.
     *
     * @file App/Shared/Helpers/user.php
     * @param string $sanitizedUserEmail User email after it has been sanitized
     * @param string $rawUserEmail The user's email.
     */
    $userEmail = Filter::getInstance()->applyFilter(
        'pre_user_email',
        (string) $sanitizedUserEmail,
        (string) $rawUserEmail
    );
    /*
     * If there is no update, just check for `email_exists`. If there is an update,
     * check if current email and new email are the same, or not, and check `email_exists`
     * accordingly.
     */
    if (
        (
            is_false__($update) || (
                    !is_false__($oldUserData)
                    && 0 !== strcasecmp(
                        $userEmail,
                        $oldUserData->email
                    )
            )
        ) && email_exists($userEmail)
    ) {
        return new UserError(
            message: esc_html__(string: 'Sorry, that email address cannot be used.', domain: 'devflow')
        );
    }

    $rawUserFname = $userdata['fname'];
    $sanitizedUserFname = $user->fname = Sanitizer::item($userdata['fname']);
    /**
     * Filters a user's first name before the user is created or updated.
     *
     * @file App/Shared/Helpers/user.php
     * @param string $sanitizedUserFname User first name after it has been sanitized.
     * @param string $rawUserFname The user's first name.
     */
    $userFname = Filter::getInstance()->applyFilter(
        'pre_user_fname',
        (string) $sanitizedUserFname,
        (string) $rawUserFname
    );

    $rawUserMname = $userdata['mname'];
    $sanitizedUserMname = $user->mname = Sanitizer::item($userdata['mname']);
    /**
     * Filters a user's middle name before the user is created or updated.
     *
     * @file App/Shared/Helpers/user.php
     * @param string $sanitizedUserMname User middlename after it has been sanitized.
     * @param string $rawUserMname The user's middle name.
     */
    $userMname = Filter::getInstance()->applyFilter(
        'pre_user_mname',
        (string) $sanitizedUserMname,
        (string) $rawUserMname
    );

    $rawUserLname = $userdata['lname'];
    $sanitizedUserLname = $user->lname = Sanitizer::item($userdata['lname']);
    /**
     * Filters a user's last name before the user is created or updated.
     *
     * @file App/Shared/Helpers/user.php
     * @param string $sanitizedUserLname User last name after it has been sanitized.
     * @param string $rawUserLname The user's last name.
     */
    $userLname = Filter::getInstance()->applyFilter(
        'pre_user_lname',
        (string) $sanitizedUserLname,
        (string) $rawUserLname
    );

    $rawUserBio = $userdata['bio'];
    $sanitizedUserBio = Sanitizer::item($userdata['bio']);
    /**
     * Filters a user's bio before the user is created or updated.
     *
     * @file App/Shared/Helpers/user.php
     * @param string $sanitizedUserBio User bio after it has been sanitized.
     * @param string $rawUserBio The user's bio.
     */
    $meta['bio'] = Filter::getInstance()->applyFilter(
        'pre_user_bio',
        (string) $sanitizedUserBio,
        (string) $rawUserBio
    );

    $rawUserTimezone = $userdata['timezone'];
    $sanitizedUserTimezone = $user->timezone = Sanitizer::item($userdata['timezone']);
    /**
     * Filters a user's timezone before the user is created or updated.
     *
     * @file App/Shared/Helpers/user.php
     * @param string $sanitizedUserTimezone User timezone after it has been sanitized.
     * @param string $rawUserTimezone The user's timezone.
     */
    $userTimezone = Filter::getInstance()->applyFilter(
        'pre_user_timezone',
        (string) $sanitizedUserTimezone,
        (string) $rawUserTimezone
    );

    $rawUserDateFormat = $userdata['dateFormat'];
    $sanitizedUserDateFormat = $user->dateFormat = Sanitizer::item($userdata['dateFormat']);
    /**
     * Filters a user's date format before the user is created or updated.
     *
     * @file App/Shared/Helpers/user.php
     * @param string $sanitizedUserDateFormat User date format after it has been sanitized.
     * @param string $rawUserDateFormat The user's date format.
     */
    $userDateFormat = Filter::getInstance()->applyFilter(
        'pre_user_date_format',
        (string) $sanitizedUserDateFormat,
        (string) $rawUserDateFormat
    );

    $rawUserTimeFormat = $userdata['timeFormat'];
    $sanitizedUserTimeFormat = $user->timeFormat = Sanitizer::item($userdata['timeFormat']);
    /**
     * Filters a user's time format before the user is created or updated.
     *
     * @file App/Shared/Helpers/user.php
     * @param string $sanitizedUserTimeFormat User time format after it has been sanitized.
     * @param string $rawUserTimeFormat The user's time format.
     */
    $userTimeFormat = Filter::getInstance()->applyFilter(
        'pre_user_time_format',
        (string) $sanitizedUserTimeFormat,
        (string) $rawUserTimeFormat
    );

    $rawUserLocale = $userdata['locale'];
    $sanitizedUserLocale = Sanitizer::item($userdata['locale']);
    /**
     * Filters a user's locale before the user is created or updated.
     *
     * @file App/Shared/Helpers/user.php
     * @param string $sanitizedUserLocale User locale after it has been sanitized.
     * @param string $rawUserLocale       The user's locale.
     */
    $userLocale = Filter::getInstance()->applyFilter(
        'pre_user_locale',
        (string) $sanitizedUserLocale,
        (string) $rawUserLocale
    );

    $rawUserStatus = $userdata['status'];
    $sanitizedUserStatus = Sanitizer::item($userdata['status']);
    /**
     * Filters a user's status before the user is created or updated.
     *
     * @file App/Shared/Helpers/user.php
     * @param string $sanitizedUserStatus User status after it has been sanitized.
     * @param string $rawUserStatus The user's status.
     */
    $meta['status'] = Filter::getInstance()->applyFilter(
        'pre_user_status',
        (string) $sanitizedUserStatus,
        (string) $rawUserStatus
    );

    $meta['role'] = $user->role;

    $userAdminLayout = '0';

    $meta['admin_layout'] = isset($userdata['admin_layout']) ? (int) $userdata['admin_layout'] : $userAdminLayout;

    $userAdminSidebar = '0';

    $meta['admin_sidebar'] = isset($userdata['admin_sidebar']) ? (int) $userdata['admin_sidebar'] : $userAdminSidebar;

    $userAdminSkin = 'skin-red';

    $meta['admin_skin'] = isset($userdata['admin_skin']) ? $userdata['admin_skin'] : $userAdminSkin;

    $userRegistered = QubusDateTimeImmutable::now();

    $userModified = QubusDateTimeImmutable::now();

    $userActivationKey = $user->activationKey = empty($userdata['activationKey']) ? '' : $userdata['activationKey'];

    $compacted = [
        'login' => $userLogin,
        'fname' => $userFname,
        'mname' => $userMname,
        'lname' => $userLname,
        'pass' => $userPass,
        'email' => $userEmail,
        'url' => $userUrl,
        'timezone' => $userTimezone,
        'dateFormat' => $userDateFormat,
        'timeFormat' => $userTimeFormat,
        'locale' => $userLocale,
        'registered' => $userRegistered->format('Y-m-d H:i:s'),
        'activationKey' => $userActivationKey,
    ];
    $userdata = unslash($compacted);

    /**
     * Filters user data before the record is created or updated.
     *
     * It only includes data in the user's table, not any user metadata.
     *
     * @file App/Shared/Helpers/user.php
     * @param array    $userdata {
     *     Values and keys for the user.
     *
     *      @type string $login        The user's login.
     *      @type string $fname        The user's first name.
     *      @type string $mname        The user's middle name.
     *      @type string $lname        The user's last name.
     *      @type string $pass         The user's password.
     *      @type string $email        The user's email.
     *      @type string $url          The user's url.
     *      @type string $timezone     The user's timezone.
     *      @type string $dateFormat   The user's date format.
     *      @type string $timeFormat   The user's time format.
     *      @type string $locale       The user's locale.
     *      @type string $registered   Timestamp describing the moment when the user registered. Defaults to
     *                                 Y-m-d h:i:s
     *      @type string $activateionKey
     * }
     * @param bool     $update Whether the user is being updated rather than created.
     * @param string|null $userID ID of the user to be updated, or NULL if the user is being created.
     */
    $userdata = Filter::getInstance()->applyFilter(
        'pre_cms_insert_user_data',
        $userdata,
        $update,
        $update ? $userId->toNative() : null
    );

    /**
     * Filters a user's meta values and keys immediately after the user is created or updated
     * and before any user meta is inserted or updated.
     *
     * @file App/Shared/Helpers/user.php
     * @param array $meta {
     *     Default meta values and keys for the user.
     *
     *     @type string $bio            The user's bio.
     *     @type string $status         The user's status.
     *     @type int    $admin_layout   The user's layout option.
     *     @type int    $admin_sidebar  The user's sidebar option.
     *     @type int    $admin_skin     The user's skin option.
     * }
     * @param object $user  User object.
     * @param bool $update  Whether the user is being updated rather than created.
     */
    $meta = Filter::getInstance()->applyFilter('insert_usermeta', $meta, $user, $update);

    $resolver = new NativeCommandHandlerResolver(
        container: ContainerFactory::make(config: config(key: 'commandbus.container'))
    );
    $odin = new Odin(bus: new SynchronousCommandBus($resolver));

    if (is_false__($update)) {

        try {
            $command = new CreateUserCommand([
                'id' => UserId::fromString($user->id),
                'fname' => new StringLiteral($userFname),
                'mname' => new StringLiteral($userMname ?? ''),
                'lname' => new StringLiteral($userLname),
                'email' => new EmailAddress($userEmail),
                'login' => new Username($userLogin),
                'token' => UserToken::fromString($user->token),
                'pass' => new StringLiteral($user->pass),
                'url' => new StringLiteral($userUrl ?? ''),
                'timezone' => new StringLiteral($userTimezone),
                'dateFormat' => new StringLiteral($userDateFormat ?? 'd F Y'),
                'timeFormat' => new StringLiteral($userTimeFormat ?? 'h:i A'),
                'locale' => new StringLiteral($userLocale ?? 'en'),
                'registered' => $userRegistered,
                'meta' => ArrayLiteral::fromNative($meta),
            ]);

            $odin->execute($command);
        } catch (CommandCouldNotBeHandledException | UnresolvableCommandHandlerException | ReflectionException $e) {
            FileLoggerFactory::getLogger()->error($e->getMessage());
        }
    } else {
        /**
         * User object.
         */
        if ($userEmail !== $oldUserData->email || $user->pass !== $oldUserData->pass) {
            $user->activationKey = '';
        }

        try {
            $command = new UpdateUserCommand([
                'id' => UserId::fromString($user->id),
                'fname' => new StringLiteral($user->fname),
                'mname' => new StringLiteral($user->mname ?? ''),
                'lname' => new StringLiteral($user->lname),
                'email' => new EmailAddress($user->email),
                'login' => new Username($user->login),
                'token' => UserToken::fromString($user->token),
                'pass' => new StringLiteral($user->pass),
                'url' => new StringLiteral($userUrl ?? ''),
                'timezone' => new StringLiteral($user->timezone),
                'dateFormat' => new StringLiteral($user->dateFormat ?? 'd F Y'),
                'timeFormat' => new StringLiteral($user->timeFormat ?? 'h:i A'),
                'locale' => new StringLiteral($userLocale ?? 'en'),
                'modified' => $userModified,
                'activationKey' => new StringLiteral($userActivationKey),
                'meta' => ArrayLiteral::fromNative($meta),
            ]);

            $odin->execute($command);
        } catch (CommandCouldNotBeHandledException | UnresolvableCommandHandlerException | ReflectionException $e) {
            FileLoggerFactory::getLogger()->error($e->getMessage());
        }
    }

    /** Set the user's role */
    $user->setRole($user->role);

    UserCachePsr16::clean($user);

    if ($update) {
        /**
         * Fires immediately after an existing user is updated.
         *
         * @file App/Shared/Helpers/user.php
         * @param string $userId    User ID.
         * @param User $oldUserData Object containing user's data prior to update.
         */
        Action::getInstance()->doAction('profile_update', $userId->toNative(), $oldUserData);
    } else {
        /**
         * Fires immediately after a new user is registered.
         *
         * @file App/Shared/Helpers/user.php
         * @param string $userId User ID.
         */
        Action::getInstance()->doAction('user_register', $userId->toNative());
    }

    return $userId->toNative();
}

/**
 * Update a user in the database.
 *
 * It is possible to update a user's password by specifying the 'user_pass'
 * value in the $userdata parameter array.
 *
 * See {@see cms_insert_user()} For what fields can be set in $userdata.
 *
 * @file App/Shared/Helpers/user.php
 * @param array|ServerRequestInterface|User $userdata An array of user data or a user object of type stdClass or User.
 * @return string|Error The updated user's id or return an Error if the user could not be updated.
 * @throws CommandPropertyNotFoundException
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 * @throws TypeException
 * @throws UnresolvableQueryHandlerException
 */
function cms_update_user(array|ServerRequestInterface|User $userdata): string|UserError
{
    if ($userdata instanceof ServerRequestInterface) {
        $userdata = $userdata->getParsedBody();
    } elseif ($userdata instanceof User) {
        $userdata = $userdata->toArray();
    }

    $id = $userdata['id'] ?? '';
    if (!$id) {
        return new UserError(message: esc_html__(string: 'Invalid user id.', domain: 'devflow'));
    }

    // First, get all the original fields
    /** @var User $userObj */
    $userObj = get_userdata($id);
    if (!$userObj) {
        return new UserError(message: esc_html__(string: 'Invalid user id.', domain: 'devflow'));
    }

    $user = get_object_vars($userObj);

    $userAttributes = [
        'timezone',
        'date_format',
        'time_format',
        'locale',
        'bio',
        'role',
        'status',
        'admin_layout',
        'admin_sidebar',
        'admin_skin'
    ];

    foreach ($userAttributes as $key) {
        $user[$key] = get_user_option($key, $user['id']);
    }

    if (!empty($userdata['pass']) && $userdata['pass'] !== $userObj->pass) {
        // If password is changing, hash it now
        $plaintextPass = $userdata['pass'];
        $userdata['pass'] = Password::hash($plaintextPass);

        /**
         * Filters whether to send the password change email.
         *
         * @file App/Shared/Helpers/user.php
         * @see cms_insert_user() For `$user` and `$userdata` fields.
         *
         * @param bool  $send     Whether to send the email.
         * @param array $user     The original user array before changes.
         * @param array $userdata The updated user array.
         *
         */
        $sendPasswordChangeEmail = Filter::getInstance()->applyFilter(
            'send_password_change_email',
            true,
            $user,
            $userdata
        );
    }

    if (isset($userdata['email']) && $user['email'] !== $userdata['email']) {
        /**
         * Filters whether to send the email change email.
         *
         * @file App/Shared/Helpers/user.php
         * @see cms_insert_user() For `$user` and `$userdata` fields.
         *
         * @param bool  $send     Whether to send the email.
         * @param array $user     The original user array before changes.
         * @param array $userdata The updated user array.
         *
         */
        $sendEmailChangeEmail = Filter::getInstance()->applyFilter(
            'send_email_change_email',
            true,
            $user,
            $userdata
        );
    }

    // Merge old and new fields with new fields overwriting old ones.
    $userdata = array_merge($user, $userdata);
    $userId = cms_insert_user($userdata);

    if (!$userId instanceof Error) {
        if (!empty($sendPasswordChangeEmail)) {
            /**
             * Fires when user is updated successfully.
             *
             * @file App/Shared/Helpers/user.php
             * @param array  $user          The original user array before changes.
             * @param string $plaintextPass Plaintext password before hashing.
             * @param array  $userdata      The updated user array.
             */
            Action::getInstance()->doAction('password_change_email', $user, $plaintextPass, $userdata);
        }

        if (!empty($sendEmailChangeEmail)) {
            /**
             * Fires when user is updated successfully.
             *
             * @file App/Shared/Helpers/user.php
             * @param array $user     The original user array before changes.
             * @param array $userdata The updated user array.
             */
            Action::getInstance()->doAction('email_change_email', $user, $userdata);
        }
    }

    /**
     * Update the cookies if the username changed.
     * @var User $currentUser
     */
    $currentUser = get_current_user();
    if ($currentUser->id === $id) {
        if (isset($userdata['login']) && $userdata['token'] !== $currentUser->token) {
            /**
             * Retrieve data from the old secure cookie to set expiration.
             */
            $oldCookieData = get_secure_cookie_data('USERCOOKIEID');
            $rememberme = $oldCookieData->remember === 'yes' ? $oldCookieData->remember : 'no';
            /**
             * Clear the old cookie data.
             */
            cms_clear_auth_cookie();
            /**
             * Set the new secure cookie.
             */
            cms_set_auth_cookie((array) $userdata, $rememberme);
        }
    }

    return $userId;
}

/**
 * Deletes a user from the user meta table. To delete user entirely from the system,
 * see `delete_site_user`.
 *
 * @file App/Shared/Helpers/user.php
 * @param string $userId ID of user being deleted.
 * @param string|null $assignId ID of user to whom posts will be assigned.
 *                              Default: NULL.
 * @return bool True on success or false on failure.
 * @throws CommandPropertyNotFoundException
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 * @throws TypeException
 */
function cms_delete_user(string $userId, ?string $assignId = null): bool
{
    /** @var User $user */
    $user = get_userdata($userId);

    if (!$user) {
        return false;
    }

    if (!is_null__($assignId)) {
        /**
         * Filter hook is triggered when assign_id is greater than zero.
         *
         * Posts will be reassigned before the user is deleted.
         *
         * @file App/Shared/Helpers/user.php
         * @param string $userId   ID of user to be deleted.
         * @param string $assignId ID of user to reassign posts to.
         *                       Default: NULL.
         */
        Filter::getInstance()->applyFilter('reassign_posts', $userId, $assignId);
    }

    /**
     * Action hook fires immediately before a user is deleted from the user meta table.
     *
     * @file App/Shared/Helpers/user.php
     * @param string      $userId  ID of the user to delete.
     * @param string|null $reassign ID of the user to reassign posts to.
     *                              Default: NULL.
     */
    Action::getInstance()->doAction('cms_delete_user', $userId, $assignId);

    $resolver = new NativeCommandHandlerResolver(
        container: ContainerFactory::make(config: config(key: 'commandbus.container'))
    );
    $odin = new Odin(bus: new SynchronousCommandBus($resolver));

    try {
        $command = new DeleteUserCommand([
            'id' => UserId::fromString($userId),
        ]);

        $odin->execute($command);
    } catch (CommandCouldNotBeHandledException | UnresolvableCommandHandlerException | ReflectionException $e) {
        FileLoggerFactory::getLogger()->error($e->getMessage());
        return false;
    }

    $dfdb = dfdb();
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

    UserCachePsr16::clean($user);

    /**
     * Action hook fires immediately after a user has been deleted from the user meta table.
     *
     * @file App/Shared/Helpers/user.php
     * @param string $userId   ID of the user who was deleted.
     * @param string $assignId ID of the user to whom posts were assigned. Default: null.
     */
    Action::getInstance()->doAction('deleted_user', $userId, $assignId);

    return true;
}

/**
 * New user email queued when account is created.
 *
 * @file App/Shared/Helpers/user.php
 * @param string $userId User id.
 * @param string $pass Plaintext password.
 * @return bool True on success, false on failure or Exception.
 * @throws EnvironmentIsBrokenException
 * @throws Exception
 * @throws ReflectionException
 */
function queue_new_user_email(string $userId, string $pass): bool
{
    $table = 'login';
    $collection = new Collection(storage_path("app/queue/{$table}"));

    $collection->begin();
    try {
        $collection->insert([
            'login_url' => login_url(),
            'domain_name' => get_domain_name(),
            'userid' => $userId,
            'pass' => Crypto::encrypt(
                $pass,
                Key::loadFromAsciiSafeString(config(key: 'auth.encryption_key'))
            ),
            'sent' => 0
        ]);
        $collection->commit();
        return true;
    } catch (BadFormatException $e) {
        $collection->rollback();
        FileLoggerFactory::getLogger()->error(
            sprintf(
                'CRYPTOFORMAT[%s]: %s',
                $e->getCode(),
                $e->getMessage()
            )
        );
        return false;
    } catch (TypeException | InvalidJsonException $e) {
        FileLoggerFactory::getLogger()->error(
            sprintf(
                'NODEQSTATE[%s]: %s',
                $e->getCode(),
                $e->getMessage()
            ),
            [
                'User Function' => 'queue_new_user_email'
            ]
        );
        return false;
    }
}

/**
 * Reset password email queued when reset button is clicked on the user's screen.
 *
 * @file App/Shared/Helpers/user.php
 * @param User $user User object.
 * @return string|bool User id on success, false on failure.
 * @throws EnvironmentIsBrokenException
 * @throws ReflectionException
 */
function queue_reset_user_password(User $user): bool|string
{
    $table = 'password_reset';
    $collection = new Collection(storage_path("app/queue/{$table}"));

    $collection->begin();
    try {
        $collection->insert([
            'login_url' => login_url(),
            'domain_name' => get_domain_name(),
            'userid' => $user->id,
            'pass' => Crypto::encrypt(
                $user->pass,
                Key::loadFromAsciiSafeString(config(key: 'auth.encryption_key'))
            ),
            'sent' => 0
        ]);
        $collection->commit();
        return (string) $user->id;
    } catch (BadFormatException $e) {
        $collection->rollback();
        FileLoggerFactory::getLogger()->error(
            sprintf(
                'CRYPTOFORMAT[%s]: %s',
                $e->getCode(),
                $e->getMessage()
            )
        );
        return false;
    } catch (Exception $e) {
        $collection->rollback();
        FileLoggerFactory::getLogger()->error(
            sprintf(
                'NODEQSTATE[%s]: %s',
                $e->getCode(),
                $e->getMessage()
            ),
            [
                'User Function' => 'queue_reset_user_password'
            ]
        );
        return false;
    }
}

/**
 * Email sent to user with new generated password.
 *
 * @file App/Shared/Helpers/user.php
 * @param object|array $user User object|array.
 * @param string $password Plaintext password.
 * @return bool True on success, false on failure or Exception.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 * @throws SessionException
 */
function send_reset_password_email(object|array $user, string $password): bool
{
    $message = '';

    $option = Options::factory();

    $siteName = $option->read(optionKey: 'sitename');

    if ($user instanceof User) {
        $user = $user->toArray();
    }

    $message .= "<p>" . sprintf(
        t__(
            msgid: "Hello %s! You requested that your password be reset. Please see your new password below: <br />",
            domain: 'devflow'
        ),
        $user['fname']
    );
    $message .= sprintf(esc_html__(string: 'Password: %s', domain: 'devflow'), $password) . "</p>";
    $message .= "<p>" . sprintf(
        esc_html__(
            string: 'If you still have problems with logging in, please contact us at <a href="mailto:%s">%s</a>.',
            domain: 'devflow'
        ),
        $option->read(optionKey: 'admin_email'),
        $option->read(optionKey: 'admin_email')
    ) . "</p>";

    $message = process_email_html($message, esc_html__(string: 'Password Reset', domain: 'devflow'));
    $headers[] = sprintf("From: %s <auto-reply@%s>", $siteName, get_domain_name());
    $headers[] = 'Content-Type: text/html; charset="UTF-8"';
    $headers[] = sprintf("X-Mailer: Devflow %s", Devflow::inst()->release());
    try {
        mail(
            to: $user['email'],
            subject: sprintf(
                esc_html__(
                    string: '[%s] Notice of Password Reset',
                    domain: 'devflow'
                ),
                $siteName
            ),
            message: $message,
            headers: $headers
        );
    } catch (\PHPMailer\PHPMailer\Exception | Exception $e) {
        Devflow::inst()::$APP->flash->error($e->getMessage());
    }

    return false;
}

/**
 * Email sent to user with changed/updated password.
 *
 * @file App/Shared/Helpers/user.php
 * @param object|array $user User array.
 * @param string $password Plaintext password.
 * @param array $userdata Updated user array.
 * @return bool True on success, false on failure or Exception.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 * @throws SessionException
 */
function send_password_change_email(object|array $user, string $password, array $userdata): bool
{
    $message = '';

    $option = Options::factory();

    $siteName = $option->read(optionKey: 'sitename');

    if ($user instanceof User) {
        $user = $user->toArray();
    }

    $message .= "<p>" . sprintf(
        t__(
            msgid: "Hello %s! This is confirmation that your password on %s was updated to: <br />",
            domain: 'devflow'
        ),
        $user['fname'],
        $option->read(optionKey: 'sitename')
    );
    $message .= sprintf(esc_html__(string: 'Password: %s', domain: 'devflow'), $password) . "</p>";
    $message .= "<p>" . sprintf(
        esc_html__(
            string: 'If you did not initiate a password change/update, please contact us at <a href="mailto:%s">%s</a>.',
            domain: 'devflow'
        ),
        $option->read(optionKey: 'admin_email'),
        $option->read(optionKey: 'admin_email')
    ) . "</p>";

    $message = process_email_html($message, esc_html__(string: 'Notice of Password Change', domain: 'devflow'));
    $headers[] = sprintf("From: %s <auto-reply@%s>", $siteName, get_domain_name());
    $headers[] = 'Content-Type: text/html; charset="UTF-8"';
    $headers[] = sprintf("X-Mailer: Devflow %s", Devflow::inst()->release());
    try {
        mail(
            to: $user['email'],
            subject: sprintf(
                esc_html__(
                    string: '[%s] Notice of Password Change',
                    domain: 'devflow'
                ),
                $siteName
            ),
            message: $message,
            headers: $headers
        );
    } catch (\PHPMailer\PHPMailer\Exception | Exception | ReflectionException $e) {
        Devflow::inst()::$APP->flash->error($e->getMessage());
    }

    return false;
}

/**
 * Email sent to user with changed/updated email.
 *
 * @file App/Shared/Helpers/user.php
 * @param object|array $user Original user array.
 * @param array $userdata Updated user array.
 * @return bool True on success, false on failure or Exception.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 * @throws SessionException
 */
function send_email_change_email(object|array $user, array $userdata): bool
{
    $message = '';

    $option = Options::factory();

    $siteName = $option->read(optionKey: 'sitename');

    if ($user instanceof User) {
        $user = $user->toArray();
    }

    $message .= "<p>" . sprintf(
        t__(
            msgid: "Hello %s! This is confirmation that your email on %s was updated to: <br />",
            domain: 'devflow'
        ),
        $user['fname'],
        $siteName
    );
    $message .= sprintf(esc_html__(string: 'Email: %s', domain: 'devflow'), $userdata['email']) . "</p>";
    $message .= "<p>" . sprintf(
        esc_html__(
            string: 'If you did not initiate an email change/update, please contact us at <a href="mailto:%s">%s</a>.',
            domain: 'devflow'
        ),
        $option->read(optionKey: 'admin_email'),
        $option->read(optionKey: 'admin_email')
    ) . "</p>";

    $message = process_email_html($message, esc_html__(string: 'Notice of Email Change', domain: 'devflow'));
    $headers[] = sprintf("From: %s <auto-reply@%s>", $siteName, get_domain_name());
    $headers[] = 'Content-Type: text/html; charset="UTF-8"';
    $headers[] = sprintf("X-Mailer: Devflow %s", Devflow::inst()->release());
    try {
        mail(
            to: $userdata['email'],
            subject: sprintf(
                esc_html__(
                    string: '[%s] Notice of Email Change',
                    domain: 'devflow'
                ),
                $siteName
            ),
            message: $message,
            headers: $headers
        );
    } catch (\PHPMailer\PHPMailer\Exception | Exception | ReflectionException $e) {
        Devflow::inst()::$APP->flash->error($e->getMessage());
    }

    return false;
}

/**
 * An extensive list of blacklisted usernames.
 *
 * Uses `blacklisted_usernames` filter.
 *
 * @file App/Shared/Helpers/user.php
 * @return array Array of blacklisted usernames.
 * @throws Exception
 * @throws ReflectionException
 */
function blacklisted_usernames(): array
{
    $blacklist = [
        '.htaccess', '.htpasswd', '.well-known', '400', '401', '403', '404',
        '405', '406', '407', '408', '409', '410', '411', '412', '413', '414',
        '415', '416', '417', '421', '422', '423', '424', '426', '428', '429',
        '431', '500', '501', '502', '503', '504', '505', '506', '507', '508',
        '509', '510', '511', 'about', 'about-us', 'abuse', 'access', 'account',
        'accounts', 'ad', 'add', 'admin', 'administration', 'administrator',
        'ads', 'advertise', 'advertising', 'aes128-ctr', 'aes128-gcm',
        'aes192-ctr', 'aes256-ctr', 'aes256-gcm', 'affiliate', 'affiliates',
        'ajax', 'alert', 'alerts', 'alpha', 'amp', 'analytics', 'api', 'app',
        'apps', 'asc', 'assets', 'atom', 'auth', 'authentication', 'authorize',
        'autoconfig', 'autodiscover', 'avatar', 'backup', 'banner', 'banners',
        'beta', 'billing', 'billings', 'blog', 'blogs', 'board', 'bookmark',
        'bookmarks', 'broadcasthost', 'business', 'buy', 'cache', 'calendar',
        'campaign', 'captcha', 'careers', 'cart', 'cas', 'categories',
        'category', 'cdn', 'cgi', 'cgi-bin', 'chacha20-poly1305', 'change',
        'channel', 'channels', 'chart', 'chat', 'checkout', 'clear', 'client',
        'close', 'cms', 'com', 'comment', 'comments', 'community', 'compare',
        'compose', 'config', 'connect', 'contact', 'contest', 'cookies', 'copy',
        'copyright', 'count', 'create', 'crossdomain.xml', 'css',
        'curve25519-sha256', 'customer', 'customers', 'customize', 'dashboard',
        'db', 'deals', 'debug', 'delete', 'desc', 'dev', 'developer',
        'developers', 'devflow', 'diffie-hellman-group-exchange-sha256',
        'diffie-hellman-group14-sha1', 'disconnect', 'discuss', 'dns', 'dns0',
        'dns1', 'dns2', 'dns3', 'dns4', 'docs', 'documentation', 'domain',
        'download', 'downloads', 'downvote', 'draft', 'drop', 'drupal',
        'ecdh-sha2-nistp256', 'ecdh-sha2-nistp384', 'ecdh-sha2-nistp521',
        'edit', 'editor', 'email', 'enterprise', 'error', 'errors', 'event',
        'events', 'example', 'exception', 'exit', 'explore', 'export',
        'extensions', 'false', 'family', 'faq', 'faqs', 'favicon.ico',
        'features', 'feed', 'feedback', 'feeds', 'file', 'files', 'filter',
        'follow', 'follower', 'followers', 'following', 'fonts', 'forgot',
        'forgot-password', 'forgotpassword', 'form', 'forms', 'forum', 'forums',
        'friend', 'friends', 'ftp', 'get', 'git', 'go', 'group', 'groups',
        'guest', 'guidelines', 'guides', 'head', 'header', 'help', 'hide',
        'hmac-sha', 'hmac-sha1', 'hmac-sha1-etm', 'hmac-sha2-256',
        'hmac-sha2-256-etm', 'hmac-sha2-512', 'hmac-sha2-512-etm', 'home',
        'host', 'hosting', 'hostmaster', 'htpasswd', 'http', 'httpd', 'https',
        'humans.txt', 'icons', 'images', 'imap', 'img', 'import', 'info',
        'insert', 'investors', 'invitations', 'invite', 'invites', 'invoice',
        'is', 'isatap', 'issues', 'it', 'jobs', 'join', 'joomla', 'js', 'json',
        'keybase.txt', 'learn', 'legal', 'license', 'licensing', 'limit',
        'live', 'load', 'local', 'localdomain', 'localhost', 'lock', 'login',
        'logout', 'lost-password', 'mail', 'mail0', 'mail1', 'mail2', 'mail3',
        'mail4', 'mail5', 'mail6', 'mail7', 'mail8', 'mail9', 'mailer-daemon',
        'mailerdaemon', 'map', 'marketing', 'marketplace', 'master', 'me',
        'media', 'member', 'members', 'message', 'messages', 'metrics', 'mis',
        'mobile', 'moderator', 'modify', 'more', 'mx', 'my', 'net', 'network',
        'new', 'news', 'newsletter', 'newsletters', 'next', 'nil', 'no-reply',
        'nobody', 'noc', 'none', 'noreply', 'notification', 'notifications',
        'ns', 'ns0', 'ns1', 'ns2', 'ns3', 'ns4', 'ns5', 'ns6', 'ns7', 'ns8',
        'ns9', 'null', 'oauth', 'oauth2', 'offer', 'offers', 'online',
        'openid', 'order', 'orders', 'overview', 'owner', 'page', 'pages',
        'partners', 'passwd', 'password', 'pay', 'payment', 'payments',
        'photo', 'photos', 'pixel', 'plans', 'plugins', 'policies', 'policy',
        'pop', 'pop3', 'popular', 'portfolio', 'content', 'postfix', 'postmaster',
        'poweruser', 'preferences', 'premium', 'press', 'previous', 'pricing',
        'print', 'privacy', 'privacy-policy', 'private', 'prod', 'product',
        'production', 'profile', 'profiles', 'project', 'projects', 'public',
        'purchase', 'put', 'quota', 'qubus', 'qubuscms', 'redirect', 'reduce',
        'refund', 'refunds', 'register', 'registration', 'remove', 'replies',
        'reply', 'request', 'request-password', 'reset', 'reset-password',
        'response', 'report', 'return', 'returns', 'review', 'reviews',
        'robots.txt', 'root', 'rootuser', 'rsa-sha2-2', 'rsa-sha2-512', 'rss',
        'rules', 'sales', 'save', 'script', 'sdk', 'search', 'secure',
        'security', 'select', 'services', 'session', 'sessions', 'settings',
        'setup', 'share', 'shift', 'shop', 'signin', 'signup', 'site', 'sitemap',
        'sites', 'smtp', 'sort', 'source', 'sql', 'ssh', 'ssh-rsa', 'ssl',
        'ssladmin', 'ssladministrator', 'sslwebmaster', 'stage', 'staging',
        'stat', 'static', 'statistics', 'stats', 'status', 'store', 'style',
        'styles', 'stylesheet', 'stylesheets', 'subdomain', 'subscribe', 'sudo',
        'super', 'superuser', 'support', 'survey', 'sync', 'sysadmin', 'system',
        'tablet', 'tag', 'tags', 'team', 'telnet', 'terms', 'terms-of-use',
        'test', 'testimonials', 'theme', 'themes', 'today', 'tools', 'topic',
        'topics', 'tour', 'training', 'translate', 'translations', 'trending',
        'trial', 'tritan', 'tritancms', 'true', 'ttcms', 'umac-128', 'undefined',
        'unfollow', 'unsubscribe', 'update', 'upgrade', 'usenet', 'user',
        'username', 'users', 'uucp', 'var', 'verify', 'video', 'view',
        'void', 'vote', 'webmail', 'webmaster', 'website', 'widget', 'widgets',
        'wiki', 'wordpress', 'wpad', 'write', 'www', 'www-data', 'www1', 'www2',
        'www3', 'www4', 'you', 'yourname', 'yourusername', 'zlib'
    ];

    return Filter::getInstance()->applyFilter('blacklisted_usernames', $blacklist);
}

/**
 * Resets a user's password.
 *
 * @file App/Shared/Helpers/user.php
 * @param string $userId ID of user whose password is to be reset.
 * @return bool|string User id on success or Exception on failure.
 * @throws CommandCouldNotBeHandledException
 * @throws EnvironmentIsBrokenException
 * @throws Exception
 * @throws ReflectionException
 * @throws SessionException
 * @throws TypeException
 * @throws UnresolvableCommandHandlerException
 */
function reset_password(string $userId): bool|string
{
    $password = generate_random_password(config(key: 'cms.password_length'));

    $user = new User();
    $user->id = $userId;
    $user->pass = $password;

    $resolver = new NativeCommandHandlerResolver(
        container: ContainerFactory::make(config: config(key: 'commandbus.container'))
    );
    $odin = new Odin(bus: new SynchronousCommandBus($resolver));

    try {
        $command = new UpdateUserPasswordCommand([
            'id' => UserId::fromString($userId),
            'token' => new UserToken(),
            'pass' => new StringLiteral($password),
        ]);

        $odin->execute($command);

        $_userId = queue_reset_user_password($user);
        Devflow::inst()::$APP->flash->success(
            t__(
                msgid: "The password reset email has been queued for sending.",
                domain: 'devflow'
            )
        );
        return $_userId;
    } catch (SessionException | CommandPropertyNotFoundException $e) {
        FileLoggerFactory::getLogger()->error($e->getMessage());
        Devflow::inst()::$APP->flash->error($e->getMessage());
    }

    return false;
}

/**
 * Print a dropdown list of users.
 *
 * @file App/Shared/Helpers/user.php
 * @param string $userId If working with active record, it will be the user's id.
 * @return void Dropdown list of users.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_users_reassign(string $userId = ''): void
{
    $dfdb = dfdb();

    $sql = "SELECT user_id FROM {$dfdb->basePrefix}user WHERE user_id IN " .
    "(SELECT DISTINCT user_id FROM {$dfdb->basePrefix}usermeta WHERE " .
    "meta_key LIKE '%$dfdb->prefix%') AND " .
    "user_id NOT IN ('$userId')";

    $listUsers = $dfdb->getResults($sql, Database::ARRAY_A);

    foreach ($listUsers as $user) {
        echo '<option value="' . esc_html($user['user_id']) . '">' . get_name(esc_html($user['user_id'])) . '</option>';
    }
}

/**
 * Retrieves a list of users by site_key.
 *
 * @file App/Shared/Helpers/user.php
 * @param string $siteKey Site key.
 * @return array|false|string User array on success.
 * @throws ContainerExceptionInterface
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_users_by_site_key(string $siteKey = ''): array|string|bool
{
    $dfdb = dfdb();

    $sql = "SELECT * FROM {$dfdb->basePrefix}user WHERE user_id IN " .
    "(SELECT DISTINCT user_id FROM {$dfdb->basePrefix}usermeta WHERE " .
    "meta_key LIKE '%{$siteKey}%')";

    return $dfdb->getResults($sql, Database::ARRAY_A);
}

/**
 * Returns the logged-in user's timezone.
 *
 * @file App/Shared/Helpers/user.php
 * @return mixed Logged in user's timezone or system's timezone if false.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_user_timezone(): mixed
{
    $userTimezone = get_user_by('id', get_current_user_id());
    if (is_user_logged_in() && $userTimezone !== false) {
        return $userTimezone->timezone;
    }
    return Options::factory()->read(optionKey: 'site_timezone') ?? config(key: 'app.timezone');
}

/**
 * Returns the logged-in user's date format.
 *
 * @file App/Shared/Helpers/user.php
 * @return mixed Logged in user's date format or system's date format if false.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_user_date_format(): mixed
{
    $userDateFormat = get_user_by('id', get_current_user_id());
    if (is_user_logged_in() && $userDateFormat !== false) {
        return $userDateFormat->dateFormat;
    }
    return Options::factory()->read(optionKey: 'date_format');
}

/**
 * Returns the logged-in user's time format.
 *
 * @file App/Shared/Helpers/user.php
 * @return mixed Logged in user's time format or system's time format if false.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_user_time_format(): mixed
{
    $userTimeFormat = get_user_by('id', get_current_user_id());
    if (is_user_logged_in() && $userTimeFormat !== false) {
        return $userTimeFormat->timeFormat;
    }
    return Options::factory()->read(optionKey: 'time_format');
}

/**
 * Returns the logged in user's datetime format.
 *
 * @file App/Shared/Helpers/user.php
 * @return string Logged in user's datetime format or system's datetime format.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_user_datetime_format(): string
{
    $dateFormat = get_user_date_format();
    $timeFormat = get_user_time_format();
    return Filter::getInstance()->applyFilter(
        'user_datetime_format',
        concat_ws($dateFormat, $timeFormat, ' '),
        $timeFormat,
        $dateFormat
    );
}

/**
 * Returns datetime based on user's date format, time format, and timezone.
 *
 * @file App/Shared/Helpers/user.php
 * @param string $string Datetime string.
 * @param string $format Format of the datetime string.
 * @return string Datetime string based on logged in user's date format,
 *                time format and timezone. Otherwise, it will use system settings.
 * @throws ContainerExceptionInterface
 * @throws Exception
 * @throws InvalidArgumentException
 * @throws NotFoundExceptionInterface
 * @throws ReflectionException
 */
function get_user_datetime(string $string, string $format = 'Y-m-d H:i:s'): string
{
    $datetime = (new DateTime($string, get_user_timezone()))->getDateTime();
    return $datetime->format($format);
}

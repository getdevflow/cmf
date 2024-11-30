<?php

declare(strict_types=1);

namespace App\Domain\User\Model;

use App\Infrastructure\Persistence\Cache\UserCachePsr16;
use App\Infrastructure\Persistence\Database;
use App\Shared\Services\MetaData;
use App\Shared\Services\Registry;
use App\Shared\Services\Sanitizer;
use App\Shared\Services\SimpleCacheObjectCacheFactory;
use Codefy\CommandBus\Exceptions\CommandPropertyNotFoundException;
use Codefy\QueryBus\UnresolvableQueryHandlerException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Qubus\EventDispatcher\ActionFilter\Action;
use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Exception;
use ReflectionException;
use stdClass;

use function md5;
use function Qubus\Security\Helpers\esc_html;
use function Qubus\Security\Helpers\purify_html;
use function Qubus\Support\Helpers\convert_array_to_object;
use function Qubus\Support\Helpers\is_null__;
use function sprintf;
use function strtolower;

final class User extends stdClass
{
    public function __construct(protected ?Database $dfdb = null)
    {
    }

    /**
     * Return only the main user fields.
     *
     * @param string $field The field to query against: 'id', 'token' or 'login'.
     * @param string $value The field value
     * @return object|false Raw user object
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function findBy(string $field, string $value): User|false
    {
        if ('' === $value) {
            return false;
        }

        $userId = match ($field) {
            'id' => $value,
            'token' => SimpleCacheObjectCacheFactory::make(namespace: 'usertoken')->get(md5($value), ''),
            'login' => SimpleCacheObjectCacheFactory::make(
                namespace: 'userlogin'
            )->get(md5(Sanitizer::username($value)), ''),
            'email' => SimpleCacheObjectCacheFactory::make('useremail')->get(md5($value), ''),
            default => false,
        };

        $dbField = match ($field) {
            'id' => 'user_id',
            'token' => 'user_token',
            'login' => 'user_login',
            'email' => 'user_email',
            default => false,
        };

        $user = null;

        if ('' !== $userId) {
            if ($data = SimpleCacheObjectCacheFactory::make(namespace: 'users')->get(md5($userId))) {
                is_array($data) ? convert_array_to_object($data) : $data;
            }
        }

        if (
            !$data = $this->dfdb->getRow(
                $this->dfdb->prepare(
                    sprintf(
                        "SELECT * 
                            FROM {$this->dfdb->basePrefix}user 
                            WHERE %s = ?",
                        $dbField
                    ),
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
            $user = $this->create($data);
            UserCachePsr16::update($user);
        }

        if (is_array($user)) {
            $user = convert_array_to_object($user);
        }

        return $user;
    }

    /**
     * Create a new instance of User. Optionally populating it
     * from a data array.
     *
     * @param array $data
     * @return User
     */
    public function create(array $data = []): User
    {
        $user = $this->__create();
        if ($data) {
            $user = $this->populate($user, $data);
        }
        return $user;
    }

    /**
     * Create a new User object.
     *
     * @return User
     */
    protected function __create(): User
    {
        return new User();
    }

    public function populate(User $user, array $data = []): self
    {
        $user->id = esc_html(string: $data['user_id']) ?? null;
        $user->login = esc_html(string: $data['user_login']) ?? null;
        $user->token = esc_html(string: $data['user_token']) ?? null;
        $user->fname = esc_html(string: $data['user_fname']) ?? null;
        $user->mname = esc_html(string: $data['user_mname']) ?? null;
        $user->lname = esc_html($data['user_lname']) ?? null;
        $user->email = esc_html(string: $data['user_email']) ?? null;
        $user->pass = esc_html(string: $data['user_pass']) ?? null;
        $user->url = esc_html(string: $data['user_url']) ?? null;
        $user->timezone = esc_html(string: $data['user_timezone']) ?? null;
        $user->dateFormat = esc_html(string: $data['user_date_format']) ?? null;
        $user->timeFormat = esc_html(string: $data['user_time_format']) ?? null;
        $user->locale = esc_html(string: $data['user_locale']) ?? null;
        $user->registered = isset($data['user_registered']) ? esc_html(string: $data['user_registered']) : null;
        $user->modified = isset($data['user_modified']) ? esc_html(string: $data['user_modified']) : null;
        $user->activationKey = is_null__($data['user_activation_key']) ?
        '' :
        esc_html(string: $data['user_activation_key']);

        return $user;
    }

    /**
     * Magic method for checking the existence of a certain custom field.
     *
     * @param string $key User meta key to check if set.
     * @return bool Whether the given user meta key is set.
     * @throws Exception
     * @throws ReflectionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __isset(string $key)
    {
        return MetaData::factory(Registry::getInstance()->get('tblPrefix') . 'usermeta')
            ->exists('user', $this->id, Registry::getInstance()->get('tblPrefix') . $key);
    }

    /**
     * Magic method for accessing custom fields.
     *
     * @param string $key User meta key to retrieve.
     * @return string Value of the given user meta key (if set). If `$key` is 'id', the user ID.
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function __get(string $key): string
    {
        $value = MetaData::factory(Registry::getInstance()->get('tblPrefix') . 'usermeta')
                ->read('user', $this->id, Registry::getInstance()->get('tblPrefix') . $key, true);
        return purify_html($value);
    }

    /**
     * Magic method for setting custom user fields.
     *
     * This method does not update custom fields in the user document. It only stores
     * the value on the User instance.
     *
     * @param string $key   User meta key.
     * @param mixed  $value User meta value.
     */
    public function __set(string $key, mixed $value): void
    {
        if ('id' === strtolower($key)) {
            $this->id = $value;
            return;
        }

        $this->{$key} = $value;
    }

    /**
     * Magic method for unsetting a certain custom field.
     *
     * @param string $key User meta key to unset.
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
     * Retrieves from the users and usermeta table.
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
     * Consults the users and usermeta tables.
     *
     * @param string $key Property
     * @return bool
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function isSet(string $key): bool
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

    /**
     * @param string $role
     * @throws CommandPropertyNotFoundException
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws TypeException
     * @throws UnresolvableQueryHandlerException
     */
    public function setRole(string $role): void
    {
        $oldRole = MetaData::factory(Registry::getInstance()->get('tblPrefix') . 'usermeta')
                ->read('user', $this->id, Registry::getInstance()->get('tblPrefix') . 'role', true);

        MetaData::factory(Registry::getInstance()->get('tblPrefix') . 'usermeta')
                ->update(
                    'user',
                    $this->id,
                    Registry::getInstance()->get('tblPrefix') . 'role',
                    $role,
                    $oldRole
                );

        /**
         * Fires after the user's role has been added/changed.
         *
         * @param string  $userId  The user id.
         * @param string  $role    The new role.
         * @param string  $oldRole The user's previous role.
         */
        Action::getInstance()->doAction('set_user_role', $this->id, $role, $oldRole);
    }
}

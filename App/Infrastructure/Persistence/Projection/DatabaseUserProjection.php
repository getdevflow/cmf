<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Projection;

use App\Domain\User\Event\UserEmailAddressWasChanged;
use App\Domain\User\Event\UserUrlWasChanged;
use App\Domain\User\Event\UserMetaWasChanged;
use App\Domain\User\Event\UserWasDeleted;
use App\Domain\User\Event\UserDateFormatWasChanged;
use App\Domain\User\Event\UserLocaleWasChanged;
use App\Domain\User\Event\UserLoginWasChanged;
use App\Domain\User\Event\UserModifiedWasChanged;
use App\Domain\User\Event\UserNameWasChanged;
use App\Domain\User\Event\UserPasswordWasChanged;
use App\Domain\User\Event\UserTimeFormatWasChanged;
use App\Domain\User\Event\UserTimezoneWasChanged;
use App\Domain\User\Event\UserTokenWasChanged;
use App\Domain\User\Event\UserWasCreated;
use App\Domain\User\Services\UserProjection;
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

use function App\Shared\Helpers\dfdb;
use function App\Shared\Helpers\update_user_option;

final class DatabaseUserProjection extends BaseProjection implements UserProjection
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
     * @param UserWasCreated $event
     * @throws CommandPropertyNotFoundException
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws TypeException
     * @throws UnresolvableQueryHandlerException
     * @throws NativeException
     */
    public function projectWhenUserWasCreated(UserWasCreated $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {
                $this->dfdb->qb()
                    ->table(tableName: $this->dfdb->basePrefix . 'user')
                    ->set([
                        'user_id' => $event->userId()->__toString(),
                        'user_login' => $event->userLogin()->__toString(),
                        'user_fname' => $event->name()->getFirstName()->toNative(),
                        'user_mname' => $event->name()->getMiddleName()->toNative(),
                        'user_lname' => $event->name()->getLastName()->toNative(),
                        'user_email' => $event->userEmail()->__toString(),
                        'user_token' => $event->userToken()->__toString(),
                        'user_pass' => $event->userPass()->__toString(),
                        'user_url' => $event->userUrl()->__toString(),
                        'user_timezone' => $event->userTimezone()->__toString(),
                        'user_date_format' => $event->userDateFormat()->toNative(),
                        'user_time_format' => $event->userTimeFormat()->toNative(),
                        'user_locale' => $event->userLocale()->toNative(),
                        'user_registered' => $event->userRegistered(),
                ])
                ->save();
            });

            if (!$event->usermeta()->isEmpty()) {
                foreach ($event->usermeta()->toNative() as $meta => $value) {
                    update_user_option($event->aggregateId()->__toString(), $meta, $value);
                }
            }
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }

    /**
     * @throws NativeException
     */
    public function projectWhenUserLoginWasChanged(UserLoginWasChanged $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {
                $this->dfdb->qb()
                    ->table(tableName: $this->dfdb->basePrefix . 'user')
                    ->set([
                        'user_login' => $event->userLogin()->__toString(),
                    ])
                    ->where('user_id = ?', $event->userId()->__toString())
                    ->update();
            });
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }

    /**
     * @throws TypeException
     * @throws NativeException
     */
    public function projectWhenUserNameWasChanged(UserNameWasChanged $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {
                $this->dfdb->qb()
                    ->table(tableName: $this->dfdb->basePrefix . 'user')
                    ->set([
                        'user_fname' => $event->userFname()->__toString(),
                        'user_mname' => $event->userMname()->__toString(),
                        'user_lname' => $event->userLname()->__toString(),
                    ])
                    ->where('user_id = ?', $event->userId()->__toString())
                    ->update();
            });
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }

    /**
     * @throws TypeException
     * @throws NativeException
     */
    public function projectWhenUserEmailAddressWasChanged(UserEmailAddressWasChanged $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {
                $this->dfdb->qb()
                    ->table(tableName: $this->dfdb->basePrefix . 'user')
                    ->set([
                        'user_email' => $event->userEmail()->__toString(),
                ])
                ->where('user_id = ?', $event->userId()->__toString())
                ->update();
            });
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }

    /**
     * @throws TypeException
     * @throws NativeException
     */
    public function projectWhenUserTokenWasChanged(UserTokenWasChanged $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {
                $this->dfdb->qb()
                    ->table(tableName: $this->dfdb->basePrefix . 'user')
                    ->set([
                        'user_token' => $event->userToken()->toNative(),
                    ])
                    ->where('user_id = ?', $event->userId()->__toString())
                    ->update();
            });
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }

    /**
     * @throws TypeException
     * @throws NativeException
     */
    public function projectWhenUserPasswordWasChanged(UserPasswordWasChanged $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {
                $this->dfdb->qb()
                    ->table(tableName: $this->dfdb->basePrefix . 'user')
                    ->set([
                        'user_pass' => $event->userPass()->__toString(),
                ])
                ->where('user_id = ?', $event->userId()->__toString())
                ->update();
            });
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }

    /**
     * @throws TypeException
     * @throws NativeException
     */
    public function projectWhenUserUrlWasChanged(UserUrlWasChanged $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {
                $this->dfdb->qb()
                    ->table(tableName: $this->dfdb->basePrefix . 'user')
                    ->set([
                        'user_url' => $event->userUrl()->toNative(),
                    ])
                    ->where('user_id = ?', $event->userId()->__toString())
                    ->update();
            });
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }

    /**
     * @throws TypeException
     * @throws NativeException
     */
    public function projectWhenUserTimezoneWasChanged(UserTimezoneWasChanged $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {
                $this->dfdb->qb()
                    ->table(tableName: $this->dfdb->basePrefix . 'user')
                    ->set([
                            'user_timezone' => $event->userTimezone()->toNative(),
                    ])
                    ->where('user_id = ?', $event->userId()->__toString())
                    ->update();
            });
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }

    /**
     * @throws TypeException
     * @throws NativeException
     */
    public function projectWhenUserDateFormatWasChanged(UserDateFormatWasChanged $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {
                $this->dfdb->qb()
                    ->table(tableName: $this->dfdb->basePrefix . 'user')
                    ->set([
                        'user_date_format' => $event->userDateFormat()->toNative(),
                    ])
                    ->where('user_id = ?', $event->userId()->__toString())
                    ->update();
            });
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }

    /**
     * @throws TypeException
     * @throws NativeException
     */
    public function projectWhenUserTimeFormatWasChanged(UserTimeFormatWasChanged $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {
                $this->dfdb->qb()
                    ->table(tableName: $this->dfdb->basePrefix . 'user')
                    ->set([
                        'user_time_format' => $event->userTimeFormat()->toNative(),
                    ])
                    ->where('user_id = ?', $event->userId()->__toString())
                    ->update();
            });
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }

    /**
     * @throws TypeException
     * @throws NativeException
     */
    public function projectWhenUserLocaleWasChanged(UserLocaleWasChanged $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {
                $this->dfdb->qb()
                    ->table(tableName: $this->dfdb->basePrefix . 'user')
                    ->set([
                        'user_locale' => $event->userLocale()->toNative(),
                    ])
                    ->where('user_id = ?', $event->userId()->__toString())
                    ->update();
            });
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }

    /**
     * @throws TypeException
     * @throws NativeException
     */
    public function projectWhenUserModifiedWasChanged(UserModifiedWasChanged $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {
                $this->dfdb->qb()
                    ->table(tableName: $this->dfdb->basePrefix . 'user')
                    ->set([
                            'user_modified' => $event->userModified(),
                    ])
                    ->where('user_id = ?', $event->userId()->__toString())
                    ->update();
            });
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }

    /**
     * @param UserMetaWasChanged $event
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws TypeException
     * @throws CommandPropertyNotFoundException
     * @throws UnresolvableQueryHandlerException
     * @throws Exception
     */
    public function projectWhenUserMetaWasChanged(UserMetaWasChanged $event): void
    {
        if (!$event->usermeta()->isEmpty()) {
            foreach ($event->usermeta()->toNative() as $key => $value) {
                update_user_option($event->aggregateId()->__toString(), $key, $value);
            }
        }
    }

    /**
     * @param UserWasDeleted $event
     * @return void
     * @throws TypeException
     * @throws NativeException
     */
    public function projectWhenUserWasDeleted(UserWasDeleted $event): void
    {
        try {
            $this->dfdb->qb()->transactional(callback: function () use ($event) {
                $this->dfdb->qb()->setStructure(primaryKeyName: 'user_id');

                $this->dfdb->qb()
                    ->table(tableName: $this->dfdb->basePrefix . 'user')
                    ->reset()
                    ->findOne($event->userId()->__toString())
                    ->delete();
            });
        } catch (OrmException $e) {
            throw new NativeException(message: $e->getMessage());
        }
    }
}

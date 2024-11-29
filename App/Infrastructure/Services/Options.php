<?php

declare(strict_types=1);

namespace App\Infrastructure\Services;

use App\Infrastructure\Persistence\Database;
use App\Shared\Services\SimpleCacheObjectCacheFactory;
use Codefy\Framework\Factory\FileLoggerFactory;
use PDOException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Qubus\EventDispatcher\ActionFilter\Action;
use Qubus\EventDispatcher\ActionFilter\Filter;
use Qubus\Exception\Data\TypeException;
use Qubus\Exception\Exception;
use Qubus\ValueObjects\Identity\Ulid;
use ReflectionException;

use function App\Shared\Helpers\maybe_serialize;
use function App\Shared\Helpers\maybe_unserialize;
use function Codefy\Framework\Helpers\app;
use function Qubus\Security\Helpers\purify_html;
use function Qubus\Support\Helpers\is_null__;
use function sprintf;

final class Options
{
    public function __construct(protected Database $dfdb, protected CacheInterface $cache)
    {
    }

    /**
     * @throws ReflectionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function factory(): Options
    {
        $dfdb = app(name: Database::class);

        return new self(
            dfdb: $dfdb,
            cache: SimpleCacheObjectCacheFactory::make(namespace: $dfdb->prefix . 'options')
        );
    }

    /**
     * Add an option to the table.
     *
     * @param string $name
     * @param mixed $value
     * @return bool
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws ReflectionException
     * @throws TypeException
     * @throws \Exception
     */
    public function create(string $name, mixed $value = ''): bool
    {
        // Make sure the option doesn't already exist
        if ($this->exists($name)) {
            return true;
        }

        $_value = maybe_serialize($value);

        $this->cache->delete($name);

        Action::getInstance()->doAction('add_option', $name, $_value);

        $optionValue = $_value;

        try {
            $this->dfdb->qb()->transactional(function () use ($name, $optionValue) {
                $this->dfdb
                    ->qb()
                    ->table($this->dfdb->prefix . 'option')
                    ->insert([
                        'option_id' => Ulid::generateAsString(),
                        'option_key' => $name,
                        'option_value' => $optionValue,
                    ]);
            });

            return true;
        } catch (PDOException $ex) {
            FileLoggerFactory::getLogger()->error(
                sprintf(
                    'OPTIONS[%s]: Error: %s',
                    $ex->getCode(),
                    $ex->getMessage()
                ),
                [
                    'Options' => 'Options::create'
                ]
            );
        }

        return false;
    }

    /**
     * Read an option from options_meta.
     *
     * Return value or $default if not found.
     *
     * @throws InvalidArgumentException
     * @throws ReflectionException|Exception
     */
    public function read($optionKey, $default = ''): mixed
    {
        $optionKey = preg_replace('/\s/', '', $optionKey);
        if (empty($optionKey)) {
            return false;
        }

        /**
         * Filter the value of an existing option before it is retrieved.
         *
         * The dynamic portion of the hook name, `$optionKey`, refers to the option_key name.
         *
         * Passing a truthy value to the filter will short-circuit retrieving
         * the option value, returning the passed value instead.
         *
         * @param bool|mixed pre_option_{$optionKey} Value to return instead of the option value.
         *                                           Default false to skip it.
         * @param string $optionKey Meta key name.
         */
        $pre = Filter::getInstance()->applyFilter("pre_option_{$optionKey}", false);

        if (false !== $pre) {
            return $pre;
        }

        try {
            $result = $this->cache->get($optionKey);

            if (empty($result)) {
                $result = $this->dfdb->getVar(
                    $this->dfdb->prepare(
                        "SELECT option_value FROM {$this->dfdb->prefix}option WHERE option_key = ? LIMIT 1",
                        [
                            $optionKey
                        ]
                    )
                );
                $this->cache->set($optionKey, $result);
            }
        } catch (PDOException | Exception | ReflectionException $e) {
            FileLoggerFactory::getLogger()->error(
                sprintf(
                    'OPTIONS[%s]: Error: %s',
                    $e->getCode(),
                    $e->getMessage()
                ),
                [
                    'Options' => 'Options::read'
                ]
            );
        }

        if (is_null__($result)) {
            return false;
        }

        if (!empty($result)) {
            $value = purify_html($result);
        } else {
            $value = $default;
        }
        /**
         * Filter the value of an existing option.
         *
         * The dynamic portion of the hook name, `$optionKey`, refers to the option name.
         *
         * @param mixed $value Value of the option. If stored serialized, it will be
         *                     unserialized prior to being returned.
         * @param string $optionKey Option name.
         */
        return Filter::getInstance()->applyFilter(
            "get_option_{$optionKey}",
            maybe_unserialize($value),
            $optionKey
        );
    }

    /**
     * Update (add if it doesn't exist) an option to option's table.
     *
     * @param string $optionKey
     * @param mixed $newvalue
     * @return bool
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws ReflectionException
     * @throws TypeException
     */
    public function update(string $optionKey, mixed $newvalue): bool
    {
        $oldvalue = $this->read($optionKey);

        // If the new and old values are the same, no need to update.
        if ($newvalue === $oldvalue) {
            return false;
        }

        if (!$this->exists($optionKey)) {
            $this->create($optionKey, $newvalue);
            return true;
        }

        $_newvalue = maybe_serialize($newvalue);

        $this->cache->delete($optionKey);

        Action::getInstance()->doAction('update_option', $optionKey, $oldvalue, $newvalue);

        $optionValue = $_newvalue;

        try {
            $result = $this->dfdb
                ->qb()
                ->table($this->dfdb->prefix . 'option')
                ->where('option_key = ?', $optionKey)
                ->update([
                    'option_value' => $optionValue,
                ]);

            return true;
        } catch (PDOException $ex) {
            FileLoggerFactory::getLogger()->error(
                sprintf(
                    'OPTIONS[%s]: Error: %s',
                    $ex->getCode(),
                    $ex->getMessage()
                ),
                [
                    'Options' => 'Options::update'
                ]
            );
        }

        return false;
    }

    /**
     * Delete an option from the table.
     *
     * @throws \Exception
     */
    public function delete(string $name): bool
    {
        $results = $this->dfdb->getRow(
            $this->dfdb->prepare(
                "SELECT * FROM {$this->dfdb->prefix}option WHERE option_key = ?",
                [
                    $name
                ]
            ),
            Database::ARRAY_A
        );

        if (is_null__($results) || !$results) {
            return false;
        }

        $this->cache->delete($name);

        Action::getInstance()->doAction('delete_option', $name);

        try {
            $this->dfdb->qb()->transactional(function () use ($results) {
                $this->dfdb
                    ->qb()
                    ->setStructure('option_id')
                    ->table($this->dfdb->prefix . 'option')
                    ->reset()
                    ->findOne($results['option_id'])
                    ->delete();
            });

            return true;
        } catch (PDOException $ex) {
            FileLoggerFactory::getLogger()->error(
                sprintf(
                    'OPTIONS[%s]: Error: %s',
                    $ex->getCode(),
                    $ex->getMessage()
                ),
                [
                    'Options' => 'Options::delete'
                ]
            );
        }

        return false;
    }

    /**
     * @param array $options
     * @return bool
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws ReflectionException
     * @throws TypeException
     */
    public function massUpdate(array $options): bool
    {
        try{
            foreach($options as $optionKey => $optionValue) {
                $this->update($optionKey, $optionValue);
            }

            return true;
        } catch (PDOException|ReflectionException $e) {
            FileLoggerFactory::getLogger()->error($e->getMessage());
            return false;
        }
    }

    /**
     * Checks if a key exists in the option table.
     *
     * @param string $optionKey Key to check against.
     * @return bool
     * @throws Exception
     * @throws ReflectionException
     */
    public function exists(string $optionKey): bool
    {
        $key = $this->dfdb->getRow(
            $this->dfdb->prepare(
                "SELECT option_id FROM {$this->dfdb->prefix}option WHERE option_key = ?",
                [
                    $optionKey
                ]
            ),
            Database::ARRAY_A
        );

        return !is_null__($key);
    }
}

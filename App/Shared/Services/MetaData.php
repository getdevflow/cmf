<?php

declare(strict_types=1);

namespace App\Shared\Services;

use App\Infrastructure\Persistence\Database;
use Codefy\CommandBus\Exceptions\CommandPropertyNotFoundException;
use Codefy\Framework\Factory\FileLoggerFactory;
use Codefy\QueryBus\UnresolvableQueryHandlerException;
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

use function App\Shared\Helpers\dfdb;
use function App\Shared\Helpers\get_object_subtype;
use function App\Shared\Helpers\maybe_serialize;
use function App\Shared\Helpers\maybe_unserialize;
use function App\Shared\Helpers\sanitize_meta;
use function array_map;
use function explode;
use function implode;
use function is_array;
use function is_string;
use function Qubus\Security\Helpers\unslash;
use function Qubus\Support\Helpers\is_false__;
use function Qubus\Support\Helpers\is_null__;
use function Qubus\Support\Helpers\is_true__;
use function sprintf;
use function strval;

final class MetaData
{
    public function __construct(protected Database $dfdb, protected CacheInterface $cache)
    {
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     * @throws NotFoundExceptionInterface
     */
    public static function factory(string $namespace = 'metadata'): MetaData
    {
        return new self(dfdb: dfdb(), cache: SimpleCacheObjectCacheFactory::make($namespace));
    }

    /**
     * Retrieve the name of the metadata table for the specified object type.
     *
     * This public function is not to be used by developers. It's use is only for _metadata
     * methods.
     *
     * @access private
     * @param string $type Type of object to get metadata table for (e.g. content, product, or user).
     * @return string Metadata document name or empty string if not exist.
     */
    private function table(string $type): string
    {
        $prefix = 'user' === $type ? $this->dfdb->basePrefix : $this->dfdb->prefix;
        $tableName = "{$type}meta";
        if (empty($prefix . $tableName)) {
            return '';
        }

        return $prefix . $tableName;
    }

    /**
     * Retrieve metadata for the specified array.
     *
     * @param string $metaType Type of array metadata is for (e.g. content or user).
     * @param string $metaTypeId ID of the array metadata is for.
     * @param string $metaKey Optional. Metadata key. If not specified, retrieve all metadata for
     *                        the specified array.
     * @param bool $single Whether to return only the first value of the specified $metaKey.
     * @return mixed Array of values.
     * @throws Exception
     * @throws ReflectionException
     */
    public function read(string $metaType, string $metaTypeId, string $metaKey = '', bool $single = false): mixed
    {
        /**
         * Filters whether to retrieve metadata of a specific type.
         *
         * The dynamic portion of the hook, `$metaType`, refers to the meta
         * array type (content or user). Returning a non-null value
         * will effectively short-circuit the function.
         *
         * @param null|string   $value     The value getMetaData() should return - a single metadata value.
         * @param string        $metaTypeId   Array ID.
         * @param string        $metaKey   Optional. Meta key.
         * @param bool          $single     Whether to return only the first value of the specified $metaKey.
         */
        $check = Filter::getInstance()->applyFilter("get_{$metaType}_metadata", null, $metaTypeId, $metaKey, $single);
        if ('' !== $check) {
            return $single && is_array($check) ? $check[0] : $check;
        }

        $metaCache = $this->cache->get($metaTypeId);

        if (is_null__($metaCache)) {
            $metaCache = $this->updateMetaDataCache($metaType, [$metaTypeId]);
            $metaCache = $metaCache[$metaTypeId];
        }

        if (!$metaKey) {
            return $metaCache;
        }

        if (isset($metaCache[$metaKey])) {
            return $single ? $metaCache[$metaKey][0] : array_map(
                '\App\Shared\Helpers\maybe_unserialize',
                $metaCache[$metaKey]
            );
        }

        return $single ? '' : [];
    }

    /**
     * Update metadata for the specified array. If no value already exists for the specified array
     * ID and metadata key, the metadata will be added.
     *
     * @param string $metaType Type of array metadata is for (e.g. content or user)
     * @param string $metaTypeId ID of the array metadata is for
     * @param string $metaKey Metadata key
     * @param mixed $metaValue Metadata value. Must be serializable if non-scalar.
     * @param mixed $prevValue Optional. If specified, only update existing metadata entries with
     *                         the specified value. Otherwise, update all entries.
     * @return bool|string Meta ID if the key didn't exist, true on successful update, false on failure.
     * @throws CommandPropertyNotFoundException
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws TypeException
     * @throws UnresolvableQueryHandlerException
     * @throws InvalidArgumentException
     */
    public function update(
        string $metaType,
        string $metaTypeId,
        string $metaKey,
        mixed $metaValue,
        mixed $prevValue = ''
    ): bool|string {
        $table = $this->table($metaType);
        if (!$table) {
            return false;
        }

        $metaSubtype = get_object_subtype($metaType, $metaTypeId);

        $column = Sanitizer::key(key: sprintf('%s_id', $metaType));

        // expected_slashed ($metaKey)
        $rawMetaKey = $metaKey;
        //$metaKey     = unslash(value: (string) $metaKey);
        $passedValue = $metaValue;
        //$metaValue   = unslash(value: (string) $metaValue);
        $metaValue = sanitize_meta($metaKey, $metaValue, $metaType, $metaSubtype);

        /**
         * Filters whether to update metadata of a specific type.
         *
         * The dynamic portion of the hook, `$metaType`, refers to the meta
         * array type (content or user). Returning a non-null value
         * will effectively short-circuit the function.
         *
         * @param null|bool $check    Whether to allow updating metadata for the given type.
         * @param string    $metaTypeId  Array ID.
         * @param string    $metaKey   Meta key.
         * @param mixed     $metaValue Meta value. Must be serializable if non-scalar.
         * @param mixed     $prevValue Optional. If specified, only update existing
         *                              metadata entries with the specified value.
         *                              Otherwise, update all entries.
         */
        $check = Filter::getInstance()->applyFilter(
            "update_{$metaType}_metadata",
            null,
            $metaTypeId,
            $metaKey,
            $metaValue,
            $prevValue
        );
        if ('' !== $check) {
            return (bool) $check;
        }

        // Compare existing value to new value if no prev value given and the key exists only once.
        if (empty($prevValue)) {
            $oldValue = $this->read($metaType, $metaTypeId, $metaKey);
            if (count($oldValue) === 1) {
                if ($oldValue[0] === $metaValue) {
                    return false;
                }
            }
        }

        $table = Sanitizer::item($table);
        $column = Sanitizer::item($column);

        $metaIds = $this->dfdb->getCol(
            $this->dfdb->prepare(
                query: sprintf("SELECT meta_id FROM %s WHERE meta_key = ? AND %s = ?", $table, $column),
                params: [
                    $metaKey,
                    $metaTypeId
                ]
            )
        );

        if (empty($metaIds)) {
            return $this->create($metaType, $metaTypeId, $rawMetaKey, $passedValue);
        }

        $_metaValue = $metaValue;

        $data = ['meta_value' => $metaValue];

        foreach ($metaIds as $metaId) {
            /**
             * Fires immediately before updating metadata of a specific type.
             *
             * The dynamic portion of the hook, `$metaType`, refers to the meta
             * array type (content or user).
             *
             * @param string  $metaId   ID of the metadata entry to update.
             * @param string  $metaTypeId  Array ID.
             * @param string $metaKey   Meta key.
             * @param mixed  $_metaValue Meta value.
             */
            Action::getInstance()->doAction("update_{$metaType}meta", $metaId, $metaTypeId, $metaKey, $_metaValue);
        }

        if (! empty($prevValue)) {
            try {
                $result = $this->dfdb->qb()->transactional(function () use (
                    $table,
                    $metaTypeId,
                    $column,
                    $metaKey,
                    $metaValue,
                    $data
                ) {
                    $this->dfdb
                        ->qb()
                        ->table($table)
                        ->where(sprintf("%s = ?", $column), $metaTypeId)
                        ->and__()
                        ->where('meta_key = ?', $metaKey)
                        ->and__()
                        ->where('meta_value = ?', $metaValue)
                        ->update($data);
                });

                if (is_null__($result)) {
                    return false;
                }
            } catch (PDOException | \Exception $ex) {
                FileLoggerFactory::getLogger()->error(sprintf('METADATA[%s]: %s', $ex->getCode(), $ex->getMessage()));
            }
        } else {
            try {
                $result = $this->dfdb->qb()->transactional(
                    function () use ($table, $metaTypeId, $column, $metaKey, $data) {
                        $this->dfdb
                        ->qb()
                        ->table($table)
                        ->where(sprintf("%s = ?", $column), $metaTypeId)
                        ->and__()
                        ->where('meta_key = ?', $metaKey)
                        ->update($data);
                    }
                );

                if (is_null__($result)) {
                    return false;
                }
            } catch (PDOException | \Exception $ex) {
                FileLoggerFactory::getLogger()->error(
                    sprintf('METADATA[%s]: %s', $ex->getCode(), $ex->getMessage())
                );
            }
        }

        $this->cache->delete($metaTypeId);

        foreach ($metaIds as $metaId) {
            /**
             * Fires immediately after updating metadata of a specific type.
             *
             * The dynamic portion of the hook, `$metaType`, refers to the meta
             * array type (content or user).
             *
             * @param string $metaId   ID of updated metadata entry.
             * @param string $metaTypeId  Array ID.
             * @param string $metaKey   Meta key.
             * @param mixed  $_metaValue Meta value.
             */
            Action::getInstance()->doAction(
                "updated_{$metaType}meta",
                $metaId,
                $metaTypeId,
                $metaKey,
                $_metaValue
            );
        }

        return true;
    }

    /**
     * Add metadata for the specified array.
     *
     * @param string $metaType Type of array metadata is for (e.g. content or user)
     * @param string $metaTypeId ID of the array metadata is for.
     * @param string $metaKey Metadata key.
     * @param mixed $metaValue Metadata value. Must be serializable if non-scalar.
     * @param bool $unique Whether the specified meta key should be unique
     *                     for the array. Optional. Default false.
     * @return false|string The meta ID on success, false on failure.
     * @throws CommandPropertyNotFoundException
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws TypeException
     * @throws UnresolvableQueryHandlerException
     * @throws \Exception
     */
    public function create(
        string $metaType,
        string $metaTypeId,
        string $metaKey,
        mixed $metaValue,
        bool $unique = false
    ): false|string {
        // Make sure the metadata doesn't already exist.
        if (!empty($this->read($metaType, $metaTypeId, $metaKey))) {
            return $this->read($metaType, $metaTypeId, $metaKey);
        }

        $table = $this->table($metaType);
        if (!$table) {
            return false;
        }

        $metaSubtype = get_object_subtype($metaType, $metaTypeId);

        $column = Sanitizer::key(key: sprintf('%s_id', $metaType));

        // expected_slashed ($metaKey)
        //$metaKey = unslash(value: (string) $metaKey);
        //$metaValue = unslash(value: (string) $metaValue);
        $metaValue = sanitize_meta($metaKey, $metaValue, $metaType, $metaSubtype);

        /**
         * Filters whether to add metadata of a specific type.
         *
         * The dynamic portion of the hook, `$metaType`, refers to the meta
         * array type (content or user). Returning a non-null value
         * will effectively short-circuit the function.
         *
         * @param null|bool $check     Whether to allow adding metadata for the given type.
         * @param string    $metaTypeId   Array ID.
         * @param string    $metaKey   Meta key.
         * @param mixed     $metaValue Meta value. Must be serializable if non-scalar.
         * @param bool      $unique    Whether the specified meta key should be unique
         *                             for the array. Optional. Default false.
         */
        $check = Filter::getInstance()->applyFilter(
            "add_{$metaType}_metadata",
            null,
            $metaTypeId,
            $metaKey,
            $metaValue,
            $unique
        );
        if ('' !== $check) {
            return $check;
        }

        $table = Sanitizer::item($table);
        $column = Sanitizer::item($column);

        if (
            $unique && $this->dfdb->getVar(
                $this->dfdb->prepare(
                    query: sprintf("SELECT COUNT(*) FROM %s WHERE meta_key = ? AND %s = ?", $table, $column),
                    params: [
                        $metaKey,
                        $metaTypeId
                    ]
                )
            )
        ) {
            return false;
        }

        $_metaValue = $metaValue;
        $metaValue = maybe_serialize($metaValue);

        /**
         * Fires immediately before meta of a specific type is added.
         *
         * The dynamic portion of the hook, `$metaType`, refers to the meta
         * array type (content or user).
         *
         * @param string    $metaTypeId  Array ID.
         * @param string $metaKey   Meta key.
         * @param mixed  $metaValue Meta value.
         */
        Action::getInstance()->doAction("add_{$metaType}meta", $metaTypeId, $metaKey, $_metaValue);

        try {
            $result = $this->dfdb->qb()->transactional(function () use (
                $table,
                $metaTypeId,
                $column,
                $metaKey,
                $metaValue
            ) {
                $metaId = Ulid::generateAsString();
                $this->dfdb
                        ->qb()
                        ->table($table)
                        ->insert([
                            'meta_id' => $metaId,
                            sprintf("%s", $column) => $metaTypeId,
                            'meta_key' => $metaKey,
                            'meta_value' => $metaValue,
                        ]);

                return $metaId;
            });
        } catch (PDOException $ex) {
            FileLoggerFactory::getLogger()->error(sprintf('METADATA[%s]: %s', $ex->getCode(), $ex->getMessage()));
        }

        if (!$result) {
            return false;
        }

        $mid = (string) $result;

        $this->cache->delete($metaTypeId);

        /**
         * Fires immediately after meta of a specific type is added.
         *
         * The dynamic portion of the hook, `$metaType`, refers to the meta
         * array type (content or user).
         *
         * @param string  $mid      The meta ID after successful update.
         * @param string  $metaTypeId  Array ID.
         * @param string $metaKey   Meta key.
         * @param mixed  $metaValue Meta value.
         */
        Action::getInstance()->doAction("added_{$metaType}meta", $mid, $metaTypeId, $metaKey, $_metaValue);

        return $mid;
    }

    /**
     * Delete metadata for the specified array.
     *
     * @param string $metaType Type of array metadata is for (e.g. content or user)
     * @param string $metaTypeId ID of the array metadata is for
     * @param string $metaKey Metadata key
     * @param mixed  $metaValue Optional. Metadata value. Must be serializable if non-scalar. If specified, only delete
     *                          metadata entries with this value. Otherwise, delete all entries with the specified
     *                          meta_key. Pass `null, `false`, or an empty string to skip this check.
     *                          (For backward compatibility, it is not possible to pass an empty string to delete
     *                          those entries with an empty string for a value.)
     * @param bool $deleteAll  Optional, default is false. If true, delete matching metadata entries for all arrays,
     *                         ignoring the specified array_id. Otherwise, only delete matching metadata entries for
     *                         the specified array_id.
     * @return bool True on successful delete, false on failure.
     * @throws Exception
     * @throws ReflectionException
     */
    public function delete(
        string $metaType,
        string $metaTypeId,
        string $metaKey,
        mixed $metaValue = '',
        bool $deleteAll = false
    ): bool {
        if (!$metaTypeId && !$deleteAll) {
            return false;
        }

        $table = $this->table($metaType);
        if (!$table) {
            return false;
        }

        $typeColumn = Sanitizer::key(key: sprintf('%s_id', $metaType));
        // expected_slashed ($metaKey)
        //$metaKey = unslash(value: (string) $metaKey);
        //$metaValue = unslash(value: (string) $metaValue);

        /**
         * Filters whether to delete metadata of a specific type.
         *
         * The dynamic portion of the hook, `$metaType`, refers to the meta
         * array type (content or user). Returning a non-null value
         * will effectively short-circuit the function.
         *
         * @param null|bool $delete     Whether to allow metadata deletion of the given type.
         * @param string    $metaTypeId  Array ID.
         * @param string    $metaKey   Meta key.
         * @param mixed     $metaValue Meta value. Must be serializable if non-scalar.
         * @param bool      $deleteAll Whether to delete the matching metadata entries
         *                              for all arrays, ignoring the specified $metaTypeId.
         *                              Default false.
         */
        $check = Filter::getInstance()->applyFilter(
            "delete_{$metaType}_metadata",
            null,
            $metaTypeId,
            $metaKey,
            $metaValue,
            $deleteAll
        );
        if ('' !== $check) {
            return (bool) $check;
        }

        $_metaValue = $metaValue;
        $metaValue = maybe_serialize($metaValue);

        $table = Sanitizer::item($table);

        $query = $this->dfdb->prepare(
            query: sprintf("SELECT meta_id FROM %s WHERE meta_key = ?", $table),
            params: [
                $metaKey
            ]
        );

        $typeColumn = Sanitizer::item($typeColumn);

        if (!$deleteAll) {
            $query .= $this->dfdb->prepare(
                query: " AND $typeColumn = ?",
                params: [
                    $metaTypeId
                ]
            );
        }

        if ('' !== $metaValue) {
            $query .= $this->dfdb->prepare(
                query: " AND meta_value = ?",
                params: [
                    $metaValue
                ]
            );
        }

        $metaIds = $this->dfdb->getCol($query);
        if (!count($metaIds)) {
            return false;
        }

        $table = Sanitizer::item($table);

        if ($deleteAll) {
            if ('' !== $metaValue && false !== $metaValue) {
                $metaTypeIds = $this->dfdb->getCol(
                    $this->dfdb->prepare(
                        query: sprintf("SELECT %s FROM %s WHERE meta_key = ? AND meta_value = ?", $typeColumn, $table),
                        params: [
                            $metaKey,
                            $metaValue
                        ]
                    )
                );
            } else {
                $metaTypeIds = $this->dfdb->getCol(
                    $this->dfdb->prepare(
                        query: sprintf("SELECT %s FROM %s WHERE meta_key = ?", $typeColumn, $table),
                        params: [
                            $metaKey
                        ]
                    )
                );
            }
        }

        /**
         * Fires immediately before deleting metadata of a specific type.
         *
         * The dynamic portion of the hook, `$metaType`, refers to the meta
         * array type (content or user).
         *
         * @param array  $metaIds   An array of metadata entry IDs to delete.
         * @param string $metaTypeId  Array ID.
         * @param string $metaKey   Meta key.
         * @param mixed  $metaValue Meta value.
         */
        Action::getInstance()->doAction("delete_{$metaType}meta", $metaIds, $metaTypeId, $metaKey, $_metaValue);

        $query = $this->dfdb->prepare(
            query: sprintf("DELETE FROM %s WHERE meta_id IN(?)", $table),
            params: [
                implode(',', $metaIds)
            ]
        );

        try {
            $count = $this->dfdb->qb()->query($query)->rowCount();
        } catch (PDOException | \Exception $ex) {
            FileLoggerFactory::getLogger()->error(sprintf('METADATA[%s]: %s', $ex->getCode(), $ex->getMessage()));
        }


        if ($count <= 0) {
            return false;
        }

        if ($deleteAll) {
            foreach ((array) $metaTypeIds as $aId) {
                $this->cache->delete($aId);
            }
        } else {
            $this->cache->delete($metaTypeId);
        }

        /**
         * Fires immediately after deleting metadata of a specific type.
         *
         * The dynamic portion of the hook name, `$metaType`, refers to the meta
         * array type (content or user).
         *
         * @param array  $metaIds   An array of deleted metadata entry IDs.
         * @param string $metaTypeId  Array ID.
         * @param string $metaKey   Meta key.
         * @param mixed  $metaValue Meta value.
         */
        Action::getInstance()->doAction("deleted_{$metaType}meta", $metaIds, $metaTypeId, $metaKey, $_metaValue);

        return true;
    }

    /**
     * Determine if a meta key is set for a given array
     *
     * @param string $metaType Type of array metadata is for (e.g. content or user)
     * @param string $metaTypeId ID of the array metadata is for
     * @param string $metaKey Metadata key.
     * @return bool True of the key is set, false if not.
     * @throws Exception
     * @throws ReflectionException
     */
    public function exists(string $metaType, string $metaTypeId, string $metaKey): bool
    {
        $check = Filter::getInstance()->applyFilter("get_{$metaType}_metadata", null, $metaTypeId, $metaKey, true);
        if ('' !== $check) {
            return (bool) $check;
        }

        $metaCache = $this->cache->get($metaTypeId);

        if (is_null__($metaCache)) {
            $metaCache = $this->updateMetaDataCache($metaType, [$metaTypeId]);
            $metaCache = $metaCache[$metaTypeId];
        }

        if (isset($metaCache->{$metaKey})) {
            return true;
        }

        return false;
    }

    /**
     * Get meta data by meta ID.
     *
     * @param string $metaType Type of array metadata is for (e.g. content or user).
     * @param string $metaId ID for a specific meta row
     * @return array|false Meta array or false.
     * @throws Exception
     * @throws ReflectionException
     */
    public function readByMid(string $metaType, string $metaId): false|array
    {
        $table = $this->table($metaType);
        if (!$table) {
            return false;
        }

        /**
         * Filters whether to retrieve metadata of a specific type by meta ID.
         *
         * The dynamic portion of the hook, `$metaType`, refers to the meta
         * object type (content, user, or site). Returning a non-null value
         * will effectively short-circuit the function.
         *
         * @param mixed  $value   The value get_metadata_by_mid() should return.
         * @param string $metaId  Meta ID.
         */
        $check = Filter::getInstance()->applyFilter("get_{$metaType}_metadata_by_mid", null, $metaId);
        if ('' !== $check) {
            return $check;
        }

        $table = Sanitizer::item($table);

        $meta = $this->dfdb->getRow(
            query: $this->dfdb->prepare(
                query: sprintf("SELECT * FROM %s WHERE meta_id = ?", $table),
                params: [
                    $metaId
                ]
            ),
            output: Database::ARRAY_A
        );


        if (empty($meta)) {
            return false;
        }

        if (isset($meta['meta_value'])) {
            $meta['meta_value'] = maybe_unserialize($meta['meta_value']);
        }

        return $meta;
    }

    /**
     * Update meta data by meta ID
     *
     * @param string $metaType Type of array metadata is for (e.g. content or user)
     * @param string $metaId ID for a specific meta row
     * @param string $metaValue Metadata value
     * @param false|string $metaKey Optional, you can provide a meta key to update it
     * @return bool True on successful update, false on failure.
     * @throws CommandPropertyNotFoundException
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws TypeException
     * @throws UnresolvableQueryHandlerException
     */
    public function updateByMid(
        string $metaType,
        string $metaId,
        string $metaValue,
        false|string $metaKey = false
    ): bool {
        $table = $this->table($metaType);
        if (!$table) {
            return false;
        }

        $column = Sanitizer::key(key: sprintf('%s_id', $metaType));

        /**
         * Filters whether to update metadata of a specific type by meta ID.
         *
         * The dynamic portion of the hook, `$metaType`, refers to the meta
         * object type (content, user, or site). Returning a non-null value
         * will effectively short-circuit the function.
         *
         * @param null|bool   $check      Whether to allow updating metadata for the given type.
         * @param string      $metaId    Meta ID.
         * @param mixed       $metaValue Meta value. Must be serializable if non-scalar.
         * @param string|bool $metaKey   Meta key, if provided.
         */
        $check = Filter::getInstance()->applyFilter(
            "update_{$metaType}_metadata_by_mid",
            null,
            $metaId,
            $metaValue,
            $metaKey
        );
        if ('' !== $check) {
            return (bool) $check;
        }

        // Fetch the meta and go on if it's found.
        if ($meta = $this->readByMid($metaType, $metaId)) {
            $originalKey = $meta['meta_key'];
            $metaTypeId = $meta[$column];

            // If a new meta_key (last parameter) was specified, change the meta key,
            // otherwise use the original key in the update statement.
            if (is_false__($metaKey)) {
                $metaKey = $originalKey;
            } elseif (!is_string($metaKey)) {
                return false;
            }

            $metaSubtype = get_object_subtype($metaType, $metaTypeId);

            // Sanitize the meta
            $_metaValue = $metaValue;
            $metaValue = sanitize_meta($metaKey, $metaValue, $metaType, $metaSubtype);
            $metaValue = maybe_serialize($metaValue);

            Action::getInstance()->doAction("update_{$metaType}meta", $metaId, $metaTypeId, $metaKey, $_metaValue);

            // Run the update query.
            try {
                $this->dfdb->qb()->transactional(function () use ($table, $metaId, $metaKey, $metaValue) {
                    $this->dfdb
                            ->qb()
                            ->table(sprintf('%s', $table))
                            ->update(
                                [
                                    'meta_key' => $metaKey,
                                    'meta_value' => $metaValue,
                                ],
                            )
                            ->where('meta_id = ?', $metaId);
                });

                $result = true;
            } catch (PDOException | \Exception $ex) {
                FileLoggerFactory::getLogger()->error(
                    sprintf('METADATA[%s]: Error: %s', $ex->getCode(), $ex->getMessage())
                );
            }

            if (!is_true__($result)) {
                return false;
            }

            // Clear the caches.
            $this->cache->delete($metaTypeId);

            Action::getInstance()->doAction("updated_{$metaType}meta", $metaId, $metaTypeId, $metaKey, $_metaValue);

            return true;
        }

        // And if the meta was not found.
        return false;
    }

    /**
     * Delete meta data by meta ID
     *
     * @param string $metaType Type of array metadata is for (e.g. content or user).
     * @param string $metaId ID for a specific meta row
     * @return bool True on successful delete, false on failure.
     * @throws Exception
     * @throws ReflectionException
     * @throws \Exception
     */
    public function deleteByMid(string $metaType, string $metaId): bool
    {
        $table = $this->table($metaType);
        if (!$table) {
            return false;
        }

        $column = Sanitizer::key(key: sprintf('%s_id', $metaType));

        /**
         * Filters whether to delete metadata of a specific type by meta ID.
         *
         * The dynamic portion of the hook, `$metaType`, refers to the meta
         * object type (content, user, or site). Returning a non-null value
         * will effectively short-circuit the function.
         *
         * @param null|bool $delete  Whether to allow metadata deletion of the given type.
         * @param string    $metaId  Meta ID.
         */
        $check = Filter::getInstance()->applyFilter("delete_{$metaType}_metadata_by_mid", null, $metaId);
        if ('' !== $check) {
            return (bool) $check;
        }

        // Fetch the meta and go on if it's found.
        if ($meta = $this->readByMid($metaType, $metaId)) {
            $metaTypeId = $meta[$column];

            Action::getInstance()->doAction(
                "delete_{$metaType}meta",
                (array) $metaId,
                $metaTypeId,
                $meta['meta_key'],
                $meta['meta_value']
            );

            // Run the query, will return true if deleted, false otherwise
            try {
                $this->dfdb->qb()->transactional(function () use ($table, $metaId) {
                    $this->dfdb
                            ->qb()
                            ->setStructure(primaryKeyName: 'meta_id')
                            ->table(tableName: sprintf('%s', $table))
                            ->reset()
                            ->findOne(id: $metaId);
                });

                $result = true;
            } catch (PDOException $ex) {
                FileLoggerFactory::getLogger()->error(
                    sprintf('METADATA[%s]: Error: %s', $ex->getCode(), $ex->getMessage())
                );
            }

            // Clear the caches.
            $this->cache->delete($metaTypeId);

            Action::getInstance()->doAction(
                "deleted_{$metaType}meta",
                (array) $metaId,
                $metaTypeId,
                $meta['meta_key'],
                $meta['meta_value']
            );

            return $result;
        }

        // Meta id was not found.
        return false;
    }

    /**
     * Update the metadata cache for the specified arrays.
     *
     * @param string $metaType Type of array metadata is for (e.g., content or user)
     * @param array|string $metaTypeIds Array or comma-delimited list of array IDs to update cache for.
     * @return array|false Metadata cache for the specified arrays, or false on failure.
     * @throws Exception
     * @throws ReflectionException
     */
    public function updateMetaDataCache(string $metaType, array|string $metaTypeIds): bool|array
    {
        $table = $this->table(type: $metaType);
        if (!$table) {
            return false;
        }

        $column = Sanitizer::key(key: sprintf('%s_id', $metaType));

        if (!is_array($metaTypeIds)) {
            $metaTypeIds = explode(separator: ',', string: $metaTypeIds);
        }

        $metaTypeIds = array_map(callback: '\strval', array: $metaTypeIds);

        /**
         * Filters whether to update metadata cache of a specific type.
         *
         * The dynamic portion of the hook, `$metaType`, refers to the meta
         * object type (content or user). Returning a non-null value
         * will effectively short-circuit the function.
         *
         * @param mixed $check    Whether to allow updating the meta cache of the given type.
         * @param array $metaTypeIds Array of object IDs to update the meta cache for.
         */
        $check = Filter::getInstance()->applyFilter("update_{$metaType}_metadata_cache", null, $metaTypeIds);
        if ('' !== $check) {
            return (bool) $check;
        }

        $nonCachedIds = [];
        $cache = [];
        foreach ($metaTypeIds as $id) {
            $cachedArray = $this->cache->get($id);
            if (is_null__($cachedArray)) {
                $nonCachedIds[] = $id;
            } else {
                $cache[$id] = $cachedArray;
            }
        }

        if (empty($nonCachedIds)) {
            return $cache;
        }

        $table = Sanitizer::item($table);
        $column = Sanitizer::item($column);

        // Get meta info
        $idList = implode(separator: ',', array: $nonCachedIds);
        $metaList = $this->dfdb->getResults(
            query: $this->dfdb->prepare(
                query: sprintf(
                    "SELECT %s, meta_key, meta_value FROM %s WHERE %s IN(?) ORDER BY meta_id ASC",
                    $column,
                    $table,
                    $column
                ),
                params: [
                    $idList
                ]
            ),
            output: Database::ARRAY_A
        );

        if (!empty($metaList)) {
            foreach ($metaList as $metarow) {
                $mpid = strval(value: $metarow[$column]);
                $mkey = $metarow['meta_key'];
                $mval = $metarow['meta_value'];
                // Force subkeys to be array type:
                if (!isset($cache[$mpid]) || !is_array($cache[$mpid])) {
                    $cache[$mpid] = [];
                }
                if (!isset($cache[$mpid][$mkey]) || !is_array(value: $cache[$mpid][$mkey])) {
                    $cache[$mpid][$mkey] = [];
                }
                // Add a value to the current pid/key:
                $cache[$mpid][$mkey][] = $mval;
            }
        }

        foreach ($nonCachedIds as $id) {
            if (!isset($cache[$id])) {
                $cache[$id] = [];
            }
            $this->cache->set($id, $cache[$id]);
        }

        return $cache;
    }
}

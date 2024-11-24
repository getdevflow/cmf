<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Shared\Services\Registry;
use App\Shared\Services\SimpleCacheObjectCacheFactory;
use Codefy\Framework\Factory\FileLoggerFactory;
use PDO;
use PDOException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Qubus\Config\ConfigContainer;
use Qubus\Dbal\Schema;
use Qubus\Error\Error;
use Qubus\Exception\Exception;
use Qubus\Expressive\OrmBuilder;
use ReflectionException;

use function array_key_exists;
use function array_map;
use function array_shift;
use function array_values;
use function Codefy\Framework\Helpers\orm;
use function count;
use function func_get_args;
use function get_object_vars;
use function gettype;
use function implode;
use function is_array;
use function is_scalar;
use function json_decode;
use function json_encode;
use function md5;
use function preg_match;
use function preg_match_all;
use function preg_split;
use function Qubus\Config\Helpers\env;
use function Qubus\Support\Helpers\is_null__;
use function sprintf;
use function strlen;
use function substr;

use const JSON_PRETTY_PRINT;

final class NativePdoDatabase implements Database
{
    protected ?Schema $schema = null;

    protected ?string $connection = null;

    public array|false $lastResult;

    public string $sitePrefix = '';

    public string $basePrefix = '';

    public string $prefix = '';

    public string $siteKey = '';

    public array $siteTables = [
        'option',
        'plugin',
        'content',
        'contentmeta',
        'contenttype',
        'product',
        'productmeta',
    ];

    public array $globalTables = [
        'user',
        'usermeta'
    ];

    public array $msGlobalTables = [
        'site'
    ];

    public string $option = '';

    public string $plugin = '';

    public string $content = '';

    public string $contentmeta = '';

    public string $contenttype = '';

    public string $site = '';

    public string $user = '';

    public string $usermeta = '';

    public string $product = '';

    public string $productmeta = '';

    /**
     * @param PDO $pdo
     * @param ConfigContainer $configContainer
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function __construct(
        protected PDO $pdo,
        protected ConfigContainer $configContainer,
    ) {
        $this->connection = $this->configContainer->getConfigKey(key: 'database.default');
        $this->basePrefix = $this->configContainer->getConfigKey(
            key: "database.connections.{$this->connection}.prefix"
        );

        $this->siteKey = Registry::getInstance()->has(id: 'siteKey') ?
        Registry::getInstance()->get('siteKey') :
        $this->basePrefix;

        $this->sitePrefix = $this->siteKey ?? $this->basePrefix;
        $this->prefix = $this->sitePrefix;
        Registry::getInstance()->set('tblPrefix', $this->prefix);

        $this->setPrefix($this->prefix);
    }

    /**
     * @inheritDoc
     */
    public function quote(string|array $str): false|string
    {
        if (!is_array($str)) {
            return $this->pdo->quote((string) $str);
        } else {
            $str = implode(',', array_map(function ($v) {
                return $this->quote($v);
            }, $str));

            if (empty($str)) {
                return 'NULL';
            }

            return $str;
        }
    }

    /**
     * @inheritDoc
     */
    public function prepare(string $query, $params): string
    {
        if (is_null__($query)) {
            return '';
        }

        if (!preg_match_all("/(\?|:)/", $query, $matches)) {
            throw new Exception(
                sprintf(
                    'The query argument of %s must have a placeholder.',
                    'NativePdoDatabase::prepare'
                )
            );
        }

        $params = func_get_args();
        array_shift($params);

        if (is_array($params[0]) && count($params) === 1) {
            $params = $params[0];
        }

        foreach ($params as $param) {
            if (!is_scalar($param) && !is_null__($param)) {
                throw new PDOException(sprintf('Unsupported value type (%s).', gettype($param)));
            }
        }

        // Count the number of valid placeholders in the query.
        $placeholders = preg_match_all("/(\?|:)/", $query, $matches);
        if (count($params) !== $placeholders) {
            throw new PDOException(
                sprintf(
                    'The query does not contain the correct number of placeholders'
                    . ' (%s) for the number of arguments passed (%s).',
                    $placeholders,
                    count($params)
                )
            );
        }

        $ps = preg_split("/'/is", $query);
        $pieces = [];
        $prev = null;
        foreach ($ps as $p) {
            $lastChar = substr($p, strlen($p) - 1);

            if ($lastChar != "\\") {
                if ($prev === null) {
                    $pieces[] = $p;
                } else {
                    $pieces[] = $prev . "'" . $p;
                    $prev = null;
                }
            } else {
                $prev .= ($prev === null ? '' : "'") . $p;
            }
        }

        $arr = [];
        $indexQuestionMark = -1;
        $matches = [];

        for ($i = 0; $i < count($pieces); $i++) {
            if ($i % 2 !== 0) {
                $arr[] = "'" . $pieces[$i] . "'";
            } else {
                $st = '';
                $s = $pieces[$i];
                while (!empty($s)) {
                    if (preg_match("/(\?|:[A-Z0-9_\-]+)/is", $s, $matches, PREG_OFFSET_CAPTURE)) {
                        $index = $matches[0][1];
                        $st .= substr($s, 0, (int) $index);
                        $key = $matches[0][0];
                        $s = substr($s, (int) $index + strlen($key));

                        if ($key === '?') {
                            $indexQuestionMark++;
                            if (array_key_exists($indexQuestionMark, $params)) {
                                $st .= $this->quote($params[$indexQuestionMark]);
                            } else {
                                throw new PDOException(sprintf('Wrong params in query at %s.', $index));
                            }
                        } else {
                            if (array_key_exists($key, $params)) {
                                $st .= $this->quote($params[$key]);
                            } else {
                                throw new PDOException(sprintf('Wrong params in query with key %s.', $key));
                            }
                        }
                    } else {
                        $st .= $s;
                        $s = null;
                    }
                }
                $arr[] = $st;
            }
        }

        $this->logPreparedStmt(implode('', $arr));

        return implode('', $arr);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     * @throws NotFoundExceptionInterface|InvalidArgumentException
     * @throws Exception
     */
    private function queryPrepared($query): void
    {
        $sql = $this->pdo->query($query);
        $results = $sql->fetchAll();

        if ($this->configContainer->getConfigKey(key: 'database.cache') === true) {
            $cache = SimpleCacheObjectCacheFactory::make(namespace: $this->prefix . 'database');
            if ($cache->has(md5($query))) {
                $results = $cache->get(md5($query));
            } else {
                $cache->set(md5($query), $results);
            }
        }

        $this->lastResult = $results;
    }

    /**
     * Retrieve an entire SQL result set from the database (i.e. many rows)
     *
     * Executes an SQL query and returns the entire SQL result.
     *
     * @param string|null $query SQL query.
     * @param string $output Optional. Any of Database::ARRAY_A | Database::ARRAY_N | Database::OBJECT |
     *                       Database::JSON_OBJECT constants. With one of the first three, return an array of rows
     *                       indexed from 0 by SQL result row number. Each row is an associative array
     *                       (column => value, ...), a numerically indexed array (0 => value, ...), or an object.
     *                       ( ->column = value ), respectively. With Database::JSON_OBJECT, return a JSON array string
     *                       representative of each row requested.
     *                       Duplicate keys are discarded.
     * @return false|string|array Database query results
     * @throws ContainerExceptionInterface
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException|Exception
     */
    public function getResults(string $query = null, string $output = Database::OBJECT): false|string|array
    {
        if ($query) {
            $this->queryPrepared($query);
        } else {
            return [];
        }

        $newArray = [];

        if ($output === Database::OBJECT) {
            return json_decode(json_encode($this->lastResult, JSON_PRETTY_PRINT), false);
        } elseif ($output === Database::JSON_OBJECT) {
            return json_encode($this->lastResult, JSON_PRETTY_PRINT); // return as json output
        } elseif ($output === Database::ARRAY_A || $output === Database::ARRAY_N) {
            if ($this->lastResult) {
                //$i = 0;
                foreach ((array) $this->lastResult as $row) {
                    if ($output === Database::ARRAY_N) {
                        $newArray[] = array_values(get_object_vars((object) $row));
                    } else {
                        $newArray[] = get_object_vars((object) $row);
                    }
                }
            }
            return $newArray;
        }
        return [];
    }

    /**
     * @inheritDoc
     * @param string|null $query
     * @param int $x
     * @param int $y
     * @return string|int|null
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function getVar(string $query = null, int $x = 0, int $y = 0): string|int|null
    {
        if ($query) {
            $this->queryPrepared($query);
        } else {
            return null;
        }

        // Extract public out of cached results based x,y values
        if (isset($this->lastResult[$y])) {
            $values = array_values(get_object_vars((object) $this->lastResult[$y]));
        }

        // If there is a value return it else return null
        return (isset($values[$x])) ? $values[$x] : null;
    }

    /**
     * @inheritDoc
     * @param string|null $query
     * @param int $x
     * @return array|null
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function getCol(string $query = null, int $x = 0): ?array
    {
        $newArray = [];
        if ($query) {
            $this->queryPrepared($query);
        } else {
            return [];
        }

        // Extract the column values
        if (is_array($this->lastResult)) {
            $j = count($this->lastResult);
            for ($i = 0; $i < $j; $i++) {
                $newArray[$i] = $this->getVar(null, $x, $i);
            }
        }

        return $newArray;
    }

    /**
     * @inheritDoc
     * @param string|null $query
     * @param string $output
     * @param int $y
     * @return object|array|null
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function getRow(string $query = null, string $output = Database::OBJECT, int $y = 0): object|array|null
    {
        if ($query) {
            $this->queryPrepared($query);
        } else {
            return null;
        }

        if (! isset($this->lastResult[ $y ])) {
            return null;
        }

        if ($output === Database::OBJECT) {
            // If the output is an object then return object using the row offset.
            return isset($this->lastResult[$y]) ? (object) $this->lastResult[$y] : null;
        } elseif ($output === Database::ARRAY_A) {
            // If the output is an associative array then return row as such.
            return isset($this->lastResult[$y]) ? get_object_vars((object) $this->lastResult[$y]) : null;
        } elseif ($output === Database::ARRAY_N) {
            // If the output is a numerical array then return row as such.
            return isset($this->lastResult[$y]) ? array_values(get_object_vars((object) $this->lastResult[$y])) : null;
        } else {
            // If invalid output type was specified.
            throw new PDOException(
                "PdoDatabase::getRow(string query, output type, int offset) -- 
                Output type must be one of: Database::OBJECT, Database::ARRAY_A, Database::ARRAY_N"
            );
        }
    }

    /**
     * @throws ReflectionException
     */
    private function logPreparedStmt($log = null): void
    {
        if (env('APP_DEBUG') === 'true') {
            FileLoggerFactory::getLogger()->error(
                sprintf(
                    'SQLQUERY[]: %s',
                    $log
                )
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function schema(): Schema
    {
        if ($this->schema === null) {
            $this->schema = orm()->schema();
        }

        return $this->schema;
    }

    /**
     * @inheritDoc
     */
    public function qb(): ?OrmBuilder
    {
        return orm();
    }

    /**
     * @inheritDoc
     * @param string $sql
     * @param array $args
     * @return false|array
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function raw(string $sql, array $args = []): false|array
    {
        $stmt = $this->pdo->prepare($sql, $args);
        $this->lastResult = $stmt->fetchAll();

        if ($this->configContainer->getConfigKey(key: 'database.cache') === true) {
            $cache = SimpleCacheObjectCacheFactory::make(namespace: $this->prefix . 'database');
            if ($cache->has(md5($sql))) {
                $this->lastResult = $cache->get(md5($sql));
            } else {
                $cache->set(md5($sql), $this->lastResult);
            }
        }

        return $this->lastResult;
    }

    /**
     * Sets the table prefix for Devflow tables.
     *
     * @param ?string $prefix          Alphanumeric name for the new prefix.
     * @param bool $setTableNames Optional. Whether the table names, e.g. Database::$post, should be updated or not.
     * @return string|Error Old prefix or Error on error
     */
    public function setPrefix(?string $prefix = null, bool $setTableNames = true): Error|string
    {
        if (is_null__($prefix) || empty($prefix)) {
            return new Error('Database prefix cannot be null.', 'invalid_db_prefix');
        }

        if (preg_match('|[^a-z0-9_]|i', $prefix)) {
            return new Error('Invalid database prefix.', 'invalid_db_prefix');
        }

        $oldPrefix = '';

        if (isset($this->basePrefix)) {
            $oldPrefix = $this->basePrefix;
        }

        $this->basePrefix = $prefix;

        if ($setTableNames) {
            foreach ($this->tables('global') as $table => $prefixed_table) {
                $this->{$table} = $prefixed_table;
            }

            if (empty($this->siteKey)) {
                return $oldPrefix;
            }

            $this->sitePrefix = $this->getSitePrefix();

            foreach ($this->tables('site') as $table => $prefixed_table) {
                $this->{$table} = $prefixed_table;
            }
        }
        return $oldPrefix;
    }

    /**
     * Sets site key.
     *
     * @param string $siteKey Site id to use.
     * @return string Previous site id.
     */
    public function setSiteKey(string $siteKey): string
    {
        $oldSiteKey = $this->siteKey;
        $this->siteKey = $siteKey;

        $this->sitePrefix = $this->getSitePrefix();

        foreach ($this->tables(scope: 'site') as $table => $prefixedTable) {
            $this->{$table} = $prefixedTable;
        }

        return $oldSiteKey;
    }

    /**
     * Gets site prefix.
     *
     * @param string|null $siteKey Optional.
     * @return string Site prefix.
     */
    public function getSitePrefix(?string $siteKey = null): string
    {
        if (null === $siteKey) {
            $siteKey = $this->siteKey;
        }
        if ($this->connection === $siteKey) {
            return $this->basePrefix;
        } else {
            return $siteKey;
        }
    }

    /**
     * Returns an array of Devflow tables.
     *
     * The scope argument can take one of the following:
     *
     * 'all' - return all the 'global' and 'site' tables.
     * 'site' - returns the site level tables.
     * 'global' - returns global tables.
     * 'ms_global' - returns multisite global tables.
     *
     * @param string $scope (Optional) Can be all, site, global or ms_global. Default: all.
     * @param bool $prefix (Optional) Whether to include table prefixes. Default: true.
     * @param string|null $siteKey (Optional) The siteKey to prefix. Default: Database::siteKey
     * @return string[] Table names.
     */
    public function tables(string $scope = 'all', bool $prefix = true, ?string $siteKey = null): array
    {
        $dispatch = [
            'all' => array_merge(
                [
                    $this->globalTables,
                    $this->siteTables
                ],
                [
                    $this->msGlobalTables
                ]
            ),
            'site' => $this->siteTables,
            'global' => array_merge($this->globalTables, $this->msGlobalTables),
            'ms_global' => $this->msGlobalTables
        ];

        $tables = $scope === '' ? $dispatch['all'] : $dispatch[$scope];

        if ($prefix) {
            if (!is_null__($siteKey)) {
                $siteKey = $this->siteKey;
            }
            $sitePrefix = $this->getSitePrefix($siteKey);
            $basePrefix = $this->basePrefix;
            foreach ($tables as $k => $table) {
                if (in_array($table, $this->globalTables)) {
                    $tables[$table] = $basePrefix . $table;
                } else {
                    $tables[$table] = $sitePrefix . $table;
                }
                unset($tables[$k]);
            }
        }
        return $tables;
    }
}

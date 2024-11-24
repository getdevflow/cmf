<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use PDOException;
use Qubus\Dbal\Schema;
use Qubus\Exception\Exception;
use Qubus\Expressive\OrmBuilder;
use ReflectionException;

interface Database
{
    /**
     * Whether to return as object.
     */
    public const string OBJECT = 'OBJECT';

    /**
     * Whether to return as an associative array.
     */
    public const string ARRAY_A = 'ARRAY_A';

    /**
     * Whether to return as a numeric array.
     */
    public const string ARRAY_N = 'ARRAY_N';

    /**
     * Whether to return as a JSON object.
     */
    public const string JSON_OBJECT = 'JSON_OBJECT';

    /**
     * Wrapper for PDO::quote.
     *
     * Taken from StackOverflow: https://stackoverflow.com/a/38049697
     *
     * @param string|array $str
     * @return false|string Quoted string.
     */
    public function quote(string|array $str): false|string;

    /**
     * @throws Exception
     * @throws ReflectionException
     */
    public function prepare(string $query, $params): string;

    /**
     * Retrieve an entire SQL result set from the database (i.e. many rows)
     *
     * Executes an SQL query and returns the entire SQL result.
     *
     * @param string|null $query SQL query.
     * @param string $output Optional. Any of self::ARRAY_A | self::ARRAY_N | self::OBJECT | self::JSON_OBJECT
     *                       constants. With one of the first three, return an array of rows indexed from 0 by SQL
     *                       result row number. Each row is an associative array (column => value, ...), a numerically
     *                       indexed array (0 => value, ...), or an object. ( ->column = value ), respectively.
     *                       With self::JSON_OBJECT, return a JSON array string representative of each row requested.
     *                       Duplicate keys are discarded.
     * @return false|string|array Database query results.
     */
    public function getResults(string $query = null, string $output = self::OBJECT): false|string|array;

    /**
     * Retrieve one variable from the database.
     *
     * Executes a SQL query and returns the value from the SQL result.
     * If the SQL result contains more than one column and/or more than one
     * row, this function returns the value in the column and row specified.
     * If $query is null, this function returns the value in the specified
     * column and row from the previous SQL result.
     *
     * @param string|null $query Optional. SQL query. Defaults to null, use the result from the previous query.
     * @param int $x Optional. Column of value to return. Indexed from 0.
     * @param int $y Optional. Row of value to return. Indexed from 0.
     * @return string|int|null Database query result (as string), or null on failure.
     */
    public function getVar(string $query = null, int $x = 0, int $y = 0): string|null|int;

    /**
     * Retrieve one column from the database.
     *
     * Executes an SQL query and returns the column from the SQL result.
     * If the SQL result contains more than one column, this function returns the column specified.
     * If $query is null, this function returns the specified column from the previous SQL result.
     *
     * @param string|null $query Optional. SQL query. Defaults to previous query.
     * @param int         $x     Optional. Column to return. Indexed from 0.
     * @return array|null Database query result. Array indexed from 0 by SQL result row number.
     */
    public function getCol(string $query = null, int $x = 0): ?array;

    /**
     * Retrieve one row from the database.
     *
     * Executes an SQL query and returns the row from the SQL result.
     *
     * @param string|null $query  SQL query.
     * @param string      $output Optional. The required return type. One of OBJECT, ARRAY_A, or ARRAY_N, which
     *                            correspond to an stdClass object, an associative array, or a numeric array,
     *                            respectively. Default self::OBJECT.
     * @param int         $y      Optional. Row to return. Indexed from 0.
     * @return array|object|null Database query result in format specified by $output or null on failure.
     * @throws PDOException
     */
    public function getRow(string $query = null, string $output = self::OBJECT, int $y = 0): object|array|null;

    /**
     * The associated schema instance.
     *
     * @return  Schema
     * @throws Exception
     */
    public function schema(): Schema;

    /**
     * Query builder.
     *
     * @return OrmBuilder|null
     * @throws PDOException
     * @throws \Exception
     */
    public function qb(): ?OrmBuilder;

    /**
     * Raw queries.
     *
     * @param string $sql
     * @param array $args
     * @return false|array
     */
    public function raw(string $sql, array $args = []): false|array;
}

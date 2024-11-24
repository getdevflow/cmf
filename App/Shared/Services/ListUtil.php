<?php

declare(strict_types=1);

namespace App\Shared\Services;

use function array_key_exists;
use function count;
use function in_array;
use function is_numeric;
use function is_object;
use function strcmp;
use function strtoupper;
use function uasort;
use function usort;

final class ListUtil
{
    private array $input = [];

    private array $output = [];

    /**
     * Temporary arguments for sorting.
     */
    private array $orderby = [];

    public function __construct($input)
    {
        $this->output = $this->input = $input;
    }

    /**
     * Returns the original input array.
     *
     * @return array The input array.
     */
    public function getInput(): array
    {
        return $this->input;
    }

    /**
     * Returns the output array.
     *
     * @return array The output array.
     */
    public function getOutput(): array
    {
        return $this->output;
    }

    /**
     * Filters the list, based on a set of key => value arguments.
     *
     * @param array  $args     Optional. An array of key => value arguments to match
     *                         against each object. Default empty array.
     * @param string $operator Optional. The logical operation to perform. 'AND' means
     *                         all elements from the array must match. 'OR' means only
     *                         one element needs to match. 'NOT' means no elements may
     *                         match. Default 'AND'.
     * @return array Array of found values.
     */
    public function filter(array $args = [], string $operator = 'AND'): array
    {
        if (empty($args)) {
            return $this->output;
        }

        $operator = strtoupper($operator);

        if (!in_array($operator, ['AND', 'OR', 'NOT'], true)) {
            return [];
        }

        $count = count($args);
        $filtered = [];

        foreach ($this->output as $key => $obj) {
            $toMatch = (array) $obj;

            $matched = 0;
            foreach ($args as $mKey => $mValue) {
                if (array_key_exists($mKey, $toMatch) && $mValue == $toMatch[$mKey]) {
                    $matched++;
                }
            }

            if (
                ('AND' == $operator && $matched == $count) ||
                    ('OR' == $operator && $matched > 0) ||
                    ('NOT' == $operator && 0 == $matched)
            ) {
                $filtered[$key] = $obj;
            }
        }

        $this->output = $filtered;

        return $this->output;
    }

    /**
     * Plucks a certain field out of each object in the list.
     *
     * This has the same functionality and prototype of
     * array_column() but also supports objects.
     *
     * @param int|string $field Field from the object to place instead of the entire object
     * @param int|string|null $indexKey Optional. Field from the object to use as keys for the new array.
     *                                  Default null.
     * @return array Array of found values. If `$indexKey` is set, an array of found values with keys
     *               corresponding to `$indexKey`. If `$indexKey` is null, array keys from the original
     *               `$list` will be preserved in the results.
     */
    public function pluck(int|string $field, int|string $indexKey = null): array
    {
        $newlist = [];

        if (!$indexKey) {
            /*
             * This is simple. Could at some point wrap array_column()
             * if we knew we had an array of arrays.
             */
            foreach ($this->output as $key => $value) {
                if (is_object($value)) {
                    $newlist[$key] = $value->{$field};
                } else {
                    $newlist[$key] = $value[$field];
                }
            }

            $this->output = $newlist;

            return $this->output;
        }

        /*
         * When index_key is not set for a particular item, push the value
         * to the end of the stack. This is how array_column() behaves.
         */
        foreach ($this->output as $value) {
            if (is_object($value)) {
                if (isset($value->{$indexKey})) {
                    $newlist[$value->{$indexKey}] = $value->{$field};
                } else {
                    $newlist[] = $value->{$field};
                }
            } else {
                if (isset($value[$indexKey])) {
                    $newlist[$value[$indexKey]] = $value[$field];
                } else {
                    $newlist[] = $value[$field];
                }
            }
        }

        $this->output = $newlist;

        return $this->output;
    }

    /**
     * Sorts the list, based on one or more orderby arguments.
     *
     * @param array|string $orderby Optional. Either the field name to order by or an array
     *                              of multiple orderby fields as $orderby => $order.
     * @param string $order         Optional. Either 'ASC' or 'DESC'. Only used if $orderby
     *                              is a string.
     * @param bool $preserverKeys   Optional. Whether to preserve keys. Default false.
     * @return array The sorted array.
     */
    public function sort(array|string $orderby = [], string $order = 'ASC', bool $preserverKeys = false): array
    {
        if (empty($orderby)) {
            return $this->output;
        }

        if (is_string($orderby)) {
            $orderby = [$orderby => $order];
        }

        foreach ($orderby as $field => $direction) {
            $orderby[$field] = 'DESC' === strtoupper($direction) ? 'DESC' : 'ASC';
        }

        $this->orderby = $orderby;

        if ($preserverKeys) {
            uasort($this->output, [$this, 'sortCallback']);
        } else {
            usort($this->output, [$this, 'sortCallback']);
        }

        $this->orderby = [];

        return $this->output;
    }

    /**
     * Callback to sort the list by specific fields.
     *
     * @param object|array $a One object to compare.
     * @param object|array $b The other object to compare.
     * @return int 0 if both objects equal. -1 if second object should come first, 1 otherwise.
     * @see ListUtil::sort()
     */
    private function sortCallback(object|array $a, object|array $b): int
    {
        if (empty($this->orderby)) {
            return 0;
        }

        $a = (array) $a;
        $b = (array) $b;

        foreach ($this->orderby as $field => $direction) {
            if (!isset($a[$field]) || !isset($b[$field])) {
                continue;
            }

            if ($a[$field] == $b[$field]) {
                continue;
            }

            $results = 'DESC' === $direction ? [1, -1] : [-1, 1];

            if (is_numeric($a[$field]) && is_numeric($b[$field])) {
                return ($a[$field] < $b[$field]) ? $results[0] : $results[1];
            }

            return 0 > strcmp($a[$field], $b[$field]) ? $results[0] : $results[1];
        }

        return 0;
    }
}

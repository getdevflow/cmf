<?php

declare(strict_types=1);

namespace App\Shared\Services;

use Codefy\Framework\Codefy;
use Qubus\EventDispatcher\ActionFilter\Filter;
use Qubus\Exception\Exception;
use ReflectionException;

use function array_merge;
use function basename;
use function Codefy\Framework\Helpers\config;
use function ctype_digit;
use function get_object_vars;
use function is_array;
use function is_object;
use function parse_str;
use function strpos;

class Utils
{
    /**
     * Parses a string into variables to be stored in an array.
     *
     * Uses {@link http://www.php.net/parse_str parse_str()}
     *
     * @param string $string The string to be parsed.
     * @param array $array Variables will be stored in this array.
     * @throws Exception
     * @throws ReflectionException
     */
    public static function parseStr(string $string, array $array): void
    {
        parse_str($string, $array);
        /**
         * Filter the array of variables derived from a parsed string.
         *
         * @param array $array The array populated with variables.
         */
        $array = Filter::getInstance()->applyFilter('parse_str', $array);
    }

    /**
     * Checks if a variable is null. If not null, check if integer or string.
     *
     * @param int|string $var  Variable to check.
     * @return string|int|null Returns null if empty otherwise a string or an integer.
     */
    public static function ifNull(int|string $var): int|string|null
    {
        $var = ctype_digit($var) ? (int) $var : (string) $var;
        return $var === '' ? null : $var;
    }

    /**
     * Merge user defined arguments into defaults array.
     *
     * @param array|string|object $args Value to merge with $defaults.
     * @param array|string $defaults Optional. Array that serves as the defaults. Default empty.
     * @return array Merged user defined values with defaults.
     * @throws Exception
     * @throws ReflectionException
     */
    public static function parseArgs(array|string|object $args, array|string $defaults = ''): array
    {
        if (is_object($args)) {
            $r = get_object_vars($args);
        } elseif (is_array($args)) {
            $r = $args;
        } else {
            self::parseStr($args, $r);
        }

        if (is_array($defaults) && $defaults) {
            return array_merge($defaults, $r);
        }

        return $r;
    }

    public static function getPathInfo(string $relative)
    {
        $base = basename(config(key: 'app.path'));
        if (strpos($_SERVER['REQUEST_URI'], Codefy::$PHP::DS . $base . $relative) === 0) {
            return $relative;
        } else {
            return $_SERVER['REQUEST_URI'];
        }
    }

    /**
     * Whether the current request is for an administrative interface.
     *
     * e.g. `/admin/`
     *
     * @return bool True if an admin screen, otherwise false.
     */
    public static function isAdmin(): bool
    {
        if (strpos(self::getPathInfo('/admin'), "/admin") === 0) {
            return true;
        }
        return false;
    }

    /**
     * Whether the current request is for a login interface.
     *
     * e.g. `/login/`
     *
     * @return bool True if login screen, otherwise false.
     */
    public static function isLogin(): bool
    {
        if (strpos(self::getPathInfo('/login'), "/login") === 0) {
            return true;
        }
        return false;
    }
}

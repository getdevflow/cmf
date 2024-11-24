<?php

declare(strict_types=1);

namespace App\Shared\Services;

use Qubus\EventDispatcher\ActionFilter\Filter;
use Qubus\Exception\Exception;
use ReflectionException;

use function array_key_exists;
use function filter_var;
use function htmlentities;
use function in_array;
use function preg_replace;
use function Qubus\Support\Helpers\is_false__;
use function Qubus\Support\Helpers\is_null__;
use function str_replace;
use function strip_tags;
use function strlen;
use function strtolower;
use function substr;
use function trim;

use const ENT_NOQUOTES;
use const FILTER_FLAG_ALLOW_FRACTION;
use const FILTER_FLAG_ALLOW_THOUSAND;
use const FILTER_SANITIZE_EMAIL;
use const FILTER_SANITIZE_FULL_SPECIAL_CHARS;
use const FILTER_SANITIZE_NUMBER_FLOAT;
use const FILTER_SANITIZE_NUMBER_INT;
use const FILTER_SANITIZE_URL;
use const FILTER_VALIDATE_BOOLEAN;
use const FILTER_VALIDATE_EMAIL;
use const FILTER_VALIDATE_INT;
use const FILTER_VALIDATE_IP;
use const FILTER_VALIDATE_REGEXP;
use const FILTER_VALIDATE_URL;

/**
 * Sanitizes different types of data.
 *
 * Examples:
 *
 *      $validations = [
 *          'name' => 'anything', 'email' => 'email', 'alias' => 'anything',
 *          'pass' => 'anything', 'phone' => 'phone', 'birthdate' => 'date'
 *      ];
 *
 *      $required = ['name', 'email', 'alias', 'pass'];
 *
 *      $sanitize = ['alias'];
 *
 *      $validate = new Sanitizer($validations, $required, $sanitize);
 *      if($validate->validateItems($_POST))
 *      {
 *          $content = $validate->items($_POST);
 *          // now do what you need, $_POST has been sanitized.
 *      }
 *
 *      Validate one item:
 *      $validate = new Sanitizer()->validateItem('email@gmail.com', 'email');
 *
 *      Sanitize one item:
 *      $sanitize = new Sanitizer()->item('<b>word</b>', 'string');
 */

final class Sanitizer
{
    public static array $regexes = [
        'date'          => "^[0-9]{1,2}[-/][0-9]{1,2}[-/][0-9]{4}\$",
        'amount'        => "^[-]?[0-9]+\$",
        'number'        => "^[-]?[0-9,]+\$",
        'alphanum'      => "^[0-9a-zA-Z ,.-_\\s\?\!]+\$",
        'not_empty'     => "[a-z0-9A-Z]+",
        'words'         => "^[A-Za-z]+[A-Za-z \\s]*\$",
        'phone'         => "^[0-9]{10,11}\$",
        'zipcode'       => "^[1-9][0-9]{3}[a-zA-Z]{2}\$",
        'plate'         => "^([0-9a-zA-Z]{2}[-]){2}[0-9a-zA-Z]{2}\$",
        'price'         => "^[0-9.,]*(([.,][-])|([.,][0-9]{2}))?\$",
        '2digitopt'     => "^\d+(\,\d{2})?\$",
        '2digitforce'   => "^\d+\,\d\d\$",
        'anything'      => "^[\d\D]{1,}\$"
    ];

    protected static array $validate = [];

    protected static array $required = [];

    protected static array $sanitize = [];

    protected static array $errors = [];

    protected static array $corrects = [];

    protected static array $fields = [];

    public function __construct(array $validate = [], array $required = [], array $sanitize = [])
    {
        self::$validate = $validate;
        self::$required = $required;
        self::$sanitize = $sanitize;
        self::$errors = [];
        self::$corrects = [];
    }

    /**
     * Validates an array of items (if needed).
     *
     * @param array $items Items to validate.
     * @return bool True if valid, false otherwise.
     */
    public static function validateItems(array $items): bool
    {
        self::$fields = $items;
        $havefailures = false;
        foreach ($items as $key => $val) {
            if (
                (strlen(string: $val) == 0 ||
                            !in_array(needle: $key, haystack: self::$validate)) &&
                    !in_array(needle: $key, haystack: self::$required)
            ) {
                self::$corrects[] = $key;
                continue;
            }
            $result = self::validateItem(item: $val, type: self::$validate[$key]);
            if ($result === false) {
                $havefailures = true;
                self::addError(field: $key, type: self::$validate[$key]);
            } else {
                self::$corrects[] = $key;
            }
        }

        return(!$havefailures);
    }

    /**
     * Sanitizes an array of items according to the self::$sanitize[].
     *
     * Sanitize will be standard of type string, but can also be specified.
     * For ease of use, this syntax is accepted:
     *
     *      $sanitize = ['fieldname', 'otherfieldname' => 'float'];
     *      $this->items($sanitize);
     *
     * @param array $items Items to sanitize.
     * @param string $context The context for which the string is being sanitized.
     * @return array Sanitized items.
     * @throws Exception
     * @throws ReflectionException
     */
    public static function items(array $items, string $context = 'save'): array
    {
        $rawItems = $items;

        foreach ($items as $key => $val) {
            if ('save' === $context) {
                $val = self::removeAccents(string: (string) $val);
            }

            if (
                !in_array(needle: $key, haystack: self::$sanitize) && !array_key_exists(
                    key: $key,
                    array: self::$sanitize
                )
            ) {
                continue;
            }
            $items[$key] = self::validateItem(item: $val, type: self::$validate[$key]);
        }

        /**
         * Filters sanitized items.
         *
         * @param string $items  Sanitized items.
         * @param string $rawItems The items prior to sanitization.
         * @param string $context The context for which the string is being sanitized.
         */
        return Filter::getInstance()->applyFilter('sanitize_items', $items, $rawItems, $context);
    }

    /**
     *
     * Adds an error to the errors array.
     */
    protected static function addError($field, $type = 'string'): void
    {
        self::$errors[$field] = $type;
    }

    /**
     * Sanitizes an item according to type.
     *
     * @param mixed $item Item to sanitize.
     * @param string $type Item type (i.e. string, float, int, etc.).
     * @param string $context The context for which the string is being sanitized.
     * @return string|null Sanitized string or null if item is empty.
     * @throws Exception
     * @throws ReflectionException
     */
    public static function item(mixed $item, string $type = 'string', string $context = 'save'): ?string
    {
        if (is_null__(var: $item)) {
            return null;
        }

        $rawItem = $item;
        $flags = 0;

        switch ($type) {
            case 'url':
                $filter = FILTER_SANITIZE_URL;
                break;
            case 'int':
                $filter = FILTER_SANITIZE_NUMBER_INT;
                break;
            case 'float':
                $filter = FILTER_SANITIZE_NUMBER_FLOAT;
                $flags = FILTER_FLAG_ALLOW_FRACTION | FILTER_FLAG_ALLOW_THOUSAND;
                break;
            case 'email':
                $item = substr(string: $item, offset: 0, length: 254);
                $filter = FILTER_SANITIZE_EMAIL;
                break;
            case 'string':
            default:
                $filter = FILTER_SANITIZE_FULL_SPECIAL_CHARS;
                $flags = FILTER_FLAG_NO_ENCODE_QUOTES;
                break;
        }

        if ('save' === $context) {
            $item = self::removeAccents(string: (string) $item);
        }

        $output = filter_var(value: $item, filter: $filter, options: $flags);

        /**
         * Filters a sanitized item.
         *
         * @param string $output  Sanitized item.
         * @param string $rawItem The item prior to sanitization.
         * @param string $context The context for which the string is being sanitized.
         */
        return Filter::getInstance()->applyFilter('sanitize_item', $output, $rawItem, $context);
    }

    /**
     * Validates a single item according to $type.
     *
     * @param mixed $item  Item to validate.
     * @param string $type Item type (i.e. string, float, int, etc.).
     * @return bool True if valid, false otherwise.
     */
    public static function validateItem(mixed $item, string $type): bool
    {
        if (array_key_exists(key: $type, array: self::$regexes)) {
            $returnval =  filter_var(
                value: $item,
                filter: FILTER_VALIDATE_REGEXP,
                options: [
                    "options" => [
                            "regexp" => '!' . self::$regexes[$type] . '!i'
                    ]
                ]
            ) !== false;
            return($returnval);
        }

        $filter = false;

        switch ($type) {
            case 'email':
                $item = substr(string: $item, offset: 0, length: 254);
                $filter = FILTER_VALIDATE_EMAIL;
                break;
            case 'int':
                $filter = FILTER_VALIDATE_INT;
                break;
            case 'boolean':
                $filter = FILTER_VALIDATE_BOOLEAN;
                break;
            case 'ip':
                $filter = FILTER_VALIDATE_IP;
                break;
            case 'url':
                $filter = FILTER_VALIDATE_URL;
                break;
        }

        if (is_false__(var: $filter)) {
            return false;
        }

        return filter_var(value: $item, filter: $filter) !== false;
    }

    /**
     * Sanitizes a string key.
     *
     * Keys are used as internal identifiers. Lowercase alphanumeric characters, dashes and underscores are allowed.
     *
     * Uses `sanitize_key` filter hook.
     *
     * @param string $key String key
     * @return string Sanitized key
     * @throws Exception
     * @throws ReflectionException
     */
    public static function key(string $key): string
    {
        $rawKey = $key;
        $key = strtolower(string: $key);
        $key = preg_replace(pattern: '/[^a-z0-9_\-]/', replacement: '', subject: $key);

        /**
         * Filters a sanitized key string.
         *
         * @param string $key     Sanitized key.
         * @param string $rawKey The key prior to sanitization.
         */
        return Filter::getInstance()->applyFilter('sanitize_key', $key, $rawKey);
    }

    /**
     * Sanitizes a username, stripping out unsafe characters.
     *
     * Removes tags, octets, entities, and if strict is enabled, will only keep
     * alphanumeric, _, space, ., -, @. After sanitizing, it passes the username,
     * raw username (the username in the parameter), and the value of $strict as
     * parameters for the `sanitize_user` filter.
     *
     * @param string $username The username to be sanitized.
     * @param bool $strict If set, limits $username to specific characters. Default false.
     * @return string The sanitized username, after passing through filters.
     * @throws Exception
     * @throws ReflectionException
     */
    public static function username(string $username, bool $strict = false): string
    {
        $rawUsername = $username;
        $username = self::removeAccents(string: $username);
        // Trim spaces at the beginning and end
        $username = trim(string: $username);
        // Replace remaining spaces with underscores
        $username = str_replace(search: ' ', replace: '_', subject: $username);
        // Kill octets
        $username = preg_replace(pattern: '|%([a-fA-F0-9][a-fA-F0-9])|', replacement: '', subject: $username);
        // Kill entities
        $username = preg_replace(pattern: '/&.+?;/', replacement: '', subject: $username);
        // If strict, reduce to ASCII for max portability.
        if ($strict) {
            $username = preg_replace(pattern: '|[^a-z0-9 _.\-@]|i', replacement: '', subject: $username);
        }
        /**
         * Filters a sanitized username string.
         *
         * @param string $username    Sanitized username.
         * @param string $rawUsername The username prior to sanitization.
         * @param bool   $strict      Whether to limit the sanitization to specific characters. Default false.
         */
        return Filter::getInstance()->applyFilter('sanitize_user', $username, $rawUsername, $strict);
    }

    public static function removeAccents(string $string, $encoding = 'UTF-8'): array|string|null
    {
        $string = strip_tags(string: $string);
        $string = htmlentities(string: $string, flags: ENT_NOQUOTES, encoding: $encoding);
        // Replace HTML entities to get the first non-accented character
        // Example: "&ecute;" => "e", "&Ecute;" => "E", "Ã " => "a" ...
        $string = preg_replace(
            pattern: '#&([A-za-z])(?:acute|grave|cedil|circ|orn|ring|slash|th|tilde|uml);#',
            replacement: '\1',
            subject: $string
        );
        // Replace ligatures such as: Œ, Æ ...
        $string = preg_replace(pattern: '#&([A-za-z]{2})(?:lig);#', replacement: '\1', subject: $string);
        // Delete apostrophes
        $string = str_replace(search: "'", replace: '', subject: $string);
        // Delete other special characters.
        $string = preg_replace(pattern: '#&[^;]+;#', replacement: '', subject: $string);

        return $string;
    }
}

<?php

declare(strict_types=1);

namespace App\Shared\Services;

use function count;
use function file_get_contents;
use function is_array;
use function token_get_all;
use function trim;

use const T_CLASS;
use const T_STRING;
use const T_WHITESPACE;

final class PhpFileParser
{
    /**
     * Get the full name (name \ namespace) of a class from its file path
     *
     * @param string $filePathName
     * @return string
     */
    public static function classFullNameFromFile(string $filePathName): string
    {
        return self::classNamespaceFromFile($filePathName) . '\\' . self::classNameFromFile($filePathName);
    }

    /**
     * Build and return an object of a class from its file path
     *
     * @param string $filePathName
     * @return mixed
     */
    public static function classObjectFromFile(string $filePathName): mixed
    {
        $classString = self::classFullNameFromFile($filePathName);

        return new $classString();
    }

    /**
     * Get the class namespace from file path using token.
     *
     * @param string $filePathName
     * @return  null|string
     */
    protected static function classNamespaceFromFile(string $filePathName): ?string
    {
        $src = file_get_contents($filePathName);

        $tokens = token_get_all($src);
        $count = count($tokens);
        $i = 0;
        $namespace = '';
        $namespace_ok = false;
        while ($i < $count) {
            $token = $tokens[$i];
            if (is_array($token) && $token[0] === T_NAMESPACE) {
                // Found namespace declaration
                while (++$i < $count) {
                    if ($tokens[$i] === ';') {
                        $namespace_ok = true;
                        $namespace = trim($namespace);
                        break;
                    }
                    $namespace .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
                }
                break;
            }
            $i++;
        }
        if (!$namespace_ok) {
            return null;
        } else {
            return $namespace;
        }
    }

    /**
     * get the class name form file path using token.
     *
     * @param string $filePathName
     * @return  mixed
     */
    protected static function classNameFromFile(string $filePathName): mixed
    {
        $phpCode = file_get_contents($filePathName);

        $classes = [];
        $tokens = token_get_all($phpCode);
        $count = count($tokens);
        for ($i = 2; $i < $count; $i++) {
            if (
                $tokens[$i - 2][0] === T_CLASS
                    && $tokens[$i - 1][0] === T_WHITESPACE
                    && $tokens[$i][0] === T_STRING
            ) {

                $class_name = $tokens[$i][1];
                $classes[] = $class_name;
            }
        }

        return $classes[0];
    }
}

<?php

declare(strict_types=1);

/*
 * This file is part of Cecil.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Util;

class Str
{
    /**
     * Combines an array into a string.
     *
     * @param string $keyToKey   The key that become the key of the new array
     * @param string $keyToValue The key that become the value of the new array
     * @param string $separator  The separtor between the key and the value in the result string
     */
    public static function combineArrayToString(
        array $array,
        string $keyToKey,
        string $keyToValue,
        string $separator = ':'
    ): string {
        $string = '';

        foreach ($array as $subArray) {
            $string .= sprintf('%s%s%s, ', $subArray[$keyToKey], $separator, $subArray[$keyToValue]);
        }

        return substr($string, 0, -2);
    }

    /**
     * Converts 'true', 'false', 'on', 'off', 'yes', 'no' to a boolean.
     *
     * @param mixed $value Value to convert
     *
     * @return bool|mixed
     */
    public static function strToBool($value)
    {
        if (\is_string($value)) {
            if (\in_array($value, ['true', 'on', 'yes'])) {
                return true;
            }
            if (\in_array($value, ['false', 'off', 'no'])) {
                return false;
            }
        }

        return $value;
    }

    /**
     * Checks if a string starts with the given string.
     */
    public static function startsWith(string $haystack, string $needle): bool
    {
        $length = \strlen($needle);

        return substr($haystack, 0, $length) === $needle;
    }

    /**
     * Checks if a string ends with the given string.
     */
    public static function endsWith(string $haystack, string $needle): bool
    {
        $length = \strlen($needle);
        if (!$length) {
            return true;
        }

        return substr($haystack, -$length) === $needle;
    }
}

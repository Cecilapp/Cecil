<?php declare(strict_types=1);

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
     * Converts an array to a string.
     *
     * ie: [0 => 'A', 1 => 'B'] become '0:A, 1:B'
     */
    public static function arrayToString(array $array, string $separator = ':'): string
    {
        $string = '';

        foreach ($array as $key => $value) {
            $string .= \sprintf('%s%s%s, ', $key, $separator, $value);
        }

        return substr($string, 0, -2);
    }

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
            $string .= \sprintf('%s%s%s, ', $subArray[$keyToKey], $separator, $subArray[$keyToValue]);
        }

        return substr($string, 0, -2);
    }
}

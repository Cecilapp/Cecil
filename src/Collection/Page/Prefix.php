<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Collection\Page;

/**
 * Class Prefix.
 */
class Prefix
{
    // https://regex101.com/r/tJWUrd/5
    // ie: "blog/2017-10-19-post-with-prefix.md" prefix is "2017-10-19"
    const PREFIX_PATTERN = '^(.*?)(([0-9]{4})-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])|[0-9]+)(-|_|\.)(.*)$';

    /**
     * Return true if the string contains a prefix.
     *
     * @param string $string
     *
     * @return bool
     */
    public static function hasPrefix(string $string): bool
    {
        if (preg_match('/'.self::PREFIX_PATTERN.'/', $string)) {
            return true;
        }

        return false;
    }

    /**
     * Return the prefix if exists.
     *
     * @param string $string
     *
     * @return string[]|null
     */
    public static function getPrefix(string $string): ?string
    {
        if (self::hasPrefix($string)) {
            preg_match('/'.self::PREFIX_PATTERN.'/', $string, $matches);

            return $matches[2];
        }

        return null;
    }

    /**
     * Return string without the prefix (if exists).
     *
     * @param string $string
     *
     * @return string
     */
    public static function subPrefix(string $string): string
    {
        if (self::hasPrefix($string)) {
            preg_match('/'.self::PREFIX_PATTERN.'/', $string, $matches);

            return $matches[1].$matches[7];
        }

        return $string;
    }
}

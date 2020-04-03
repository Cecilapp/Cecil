<?php
/**
 * This file is part of the Cecil/Cecil package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Collection\Page;

use Cecil\Exception\Exception;

/**
 * Class PrefixSuffix.
 */
class PrefixSuffix
{
    // https://regex101.com/r/tJWUrd/6
    // ie: "blog/2017-10-19-post-1.md" prefix is "2017-10-19"
    // ie: "projet/1-projet-a.md" prefix is "1"
    const PREFIX_PATTERN = '^(|.*\/)(([0-9]{4})-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])|[0-9]+)(-|_|\.)(.*)$';
    // https://regex101.com/r/GlgBdT/7
    // ie: "blog/2017-10-19-post-1.en.md" suffix is "en"
    // ie: "projet/1-projet-a.fr-FR.md" suffix is "fr-FR"
    const SUFFIX_PATTERN = '^(.*)\.([a-z]{2}(-[A-Z]{2})?)$';

    /**
     * Returns true if the string contains a prefix or a suffix.
     *
     * @param string $string
     * @param string $type
     *
     * @return bool
     */
    protected static function has(string $string, string $type): bool
    {
        return (bool) preg_match('/'.self::getPattern($type).'/', $string);
    }

    /**
     * Returns true if the string contains a prefix.
     *
     * @param string $string
     *
     * @return bool
     */
    public static function hasPrefix(string $string): bool
    {
        return self::has($string, 'prefix');
    }

    /**
     * Returns true if the string contains a suffix.
     *
     * @param string $string
     *
     * @return bool
     */
    public static function hasSuffix(string $string): bool
    {
        return self::has($string, 'suffix');
    }

    /**
     * Returns the prefix or the suffix if exists.
     *
     * @param string $string
     * @param string $type
     *
     * @return string[]|null
     */
    protected static function get(string $string, string $type): ?string
    {
        if (self::has($string, $type)) {
            preg_match('/'.self::getPattern($type).'/', $string, $matches);
            switch ($type) {
                case 'prefix':
                    return $matches[2];
                case 'suffix':
                    return $matches[2];
            }
        }

        return null;
    }

    /**
     * Returns the prefix if exists.
     *
     * @param string $string
     *
     * @return string[]|null
     */
    public static function getPrefix(string $string): ?string
    {
        return self::get($string, 'prefix');
    }

    /**
     * Returns the suffix if exists.
     *
     * @param string $string
     *
     * @return string[]|null
     */
    public static function getSuffix(string $string): ?string
    {
        return self::get($string, 'suffix');
    }

    /**
     * Returns string without the prefix and the suffix (if exists).
     *
     * @param string $string
     *
     * @return string
     */
    public static function sub(string $string): string
    {
        if (self::hasPrefix($string)) {
            preg_match('/'.self::getPattern('prefix').'/', $string, $matches);

            $string = $matches[1].$matches[7];
        }
        if (self::hasSuffix($string)) {
            preg_match('/'.self::getPattern('suffix').'/', $string, $matches);

            $string = $matches[1];
        }

        return $string;
    }

    /**
     * Returns string without the prefix (if exists).
     *
     * @param string $string
     *
     * @return string
     */
    public static function subPrefix(string $string): string
    {
        if (self::hasPrefix($string)) {
            preg_match('/'.self::getPattern('prefix').'/', $string, $matches);

            return $matches[1].$matches[7];
        }

        return $string;
    }

    /**
     * Returns expreg pattern by $type.
     *
     * @param string $type
     *
     * @throws Exception
     *
     * @return string
     */
    protected static function getPattern(string $type): string
    {
        switch ($type) {
            case 'prefix':
                return self::PREFIX_PATTERN;
            case 'suffix':
                return self::SUFFIX_PATTERN;
            default:
                throw new Exception(\sprintf('%s must be "prefix" or "suffix"', $type));
        }
    }
}

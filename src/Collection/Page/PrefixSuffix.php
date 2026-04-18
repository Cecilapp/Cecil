<?php

/**
 * This file is part of Cecil.
 *
 * (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cecil\Collection\Page;

use Cecil\Config;

/**
 * PrefixSuffix class.
 *
 * Handles prefixes and suffixes in page filenames.
 * Prefixes can be dates or numbers, and suffixes are typically language codes.
 */
class PrefixSuffix
{
    // Match index of the prefix value (number/date) from PREFIX_PATTERN.
    private const PREFIX_PART = 2;
    // Match index of the date year part from PREFIX_PATTERN (empty for numeric prefix).
    private const PREFIX_DATE_YEAR_PART = 3;
    // Match index of the separator ("-" or "_") from PREFIX_PATTERN.
    private const PREFIX_SEPARATOR_PART = 6;
    // Match index of the string without prefix from PREFIX_PATTERN.
    private const PREFIX_SUFFIX_PART = 7;

    // https://regex101.com/r/tJWUrd/6
    // ie: "blog/2017-10-19_post-1.md" prefix is "2017-10-19"
    // ie: "projet/1-projet-a.md" prefix is "1"
    public const PREFIX_PATTERN = '(|.*\/)(([0-9]{4})-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])|[0-9]+)(-|_)(.*)';

    // https://regex101.com/r/GlgBdT/7
    // ie: "blog/2017-10-19_post-1.en.md" suffix is "en"
    // ie: "projet/1-projet-a.fr-FR.md" suffix is "fr-FR"
    public const SUFFIX_PATTERN = '(.*)\.' . Config::LANG_CODE_PATTERN;

    /**
     * Returns true if the string contains a prefix or a suffix.
     */
    protected static function has(string $string, string $type): bool
    {
        return (bool) preg_match('/^' . self::getPattern($type) . '$/', $string);
    }

    /**
     * Returns true if the string contains a prefix.
     */
    public static function hasPrefix(string $string): bool
    {
        if (!self::matchPrefix($string, $matches)) {
            return false;
        }

        return !self::isDashSeparatedNumericPrefix($matches);
    }

    /**
     * Returns true if the string contains a suffix.
     */
    public static function hasSuffix(string $string): bool
    {
        return self::has($string, 'suffix');
    }

    /**
     * Returns the prefix or the suffix if exists.
     */
    protected static function get(string $string, string $type): ?string
    {
        if (self::has($string, $type)) {
            preg_match('/^' . self::getPattern($type) . '$/', $string, $matches);
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
     */
    public static function getPrefix(string $string): ?string
    {
        if (!self::matchPrefix($string, $matches) || self::isDashSeparatedNumericPrefix($matches)) {
            return null;
        }

        return $matches[self::PREFIX_PART];
    }

    /**
     * Returns the suffix if exists.
     */
    public static function getSuffix(string $string): ?string
    {
        return self::get($string, 'suffix');
    }

    /**
     * Returns string without the prefix and the suffix (if exists).
     */
    public static function sub(string $string): string
    {
        if (self::hasPrefix($string)) {
            self::matchPrefix($string, $matches);
            $string = $matches[1] . $matches[self::PREFIX_SUFFIX_PART];
        }
        if (self::hasSuffix($string)) {
            preg_match('/^' . self::getPattern('suffix') . '$/', $string, $matches);

            $string = $matches[1];
        }

        return $string;
    }

    /**
     * Returns string without the prefix (if exists).
     */
    public static function subPrefix(string $string): string
    {
        if (self::hasPrefix($string)) {
            self::matchPrefix($string, $matches);

            return $matches[1] . $matches[self::PREFIX_SUFFIX_PART];
        }

        return $string;
    }

    /**
     * Returns expreg pattern by $type.
     *
     * @throws \InvalidArgumentException
     */
    protected static function getPattern(string $type): string
    {
        switch ($type) {
            case 'prefix':
                return self::PREFIX_PATTERN;
            case 'suffix':
                return self::SUFFIX_PATTERN;
            default:
                throw new \InvalidArgumentException('Argument must be "prefix" or "suffix".');
        }
    }

    /**
     * Matches string with prefix pattern.
     *
     * @param string     $string  String to test
     * @param array|null $matches Output parameter populated with preg_match() matches
     *
     * @return bool True when the string matches PREFIX_PATTERN
     */
    private static function matchPrefix(string $string, ?array &$matches = null): bool
    {
        return (bool) preg_match('/^' . self::getPattern('prefix') . '$/', $string, $matches);
    }

    /**
     * Returns true when the string starts with a numeric prefix separated by "-".
     *
     * @param array $matches Matches found by matchPrefix()
     */
    private static function isDashSeparatedNumericPrefix(array $matches): bool
    {
        return $matches[self::PREFIX_SEPARATOR_PART] === '-' && $matches[self::PREFIX_DATE_YEAR_PART] === '' && ctype_digit($matches[self::PREFIX_PART]);
    }
}

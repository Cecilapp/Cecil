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
    // https://regex101.com/r/tJWUrd/6
    // ie: "blog/2017-10-19_post-1.md" prefix is "2017-10-19"
    // ie: "projet/1_projet-a.md" prefix is "1"
    private const PREFIX_BASE = '(|.*\/)(([0-9]{4})-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])|[0-9]+)';
    private const PREFIX_TAIL = '(.*)';

    /** @var string[] Default prefix separators. */
    public const DEFAULT_SEPARATORS = ['-', '_'];

    // Match index of the prefix value (number/date) from PREFIX_PATTERN.
    private const PREFIX_PART = 2;
    // Match index of the string without prefix from PREFIX_PATTERN.
    private const PREFIX_SUFFIX_PART = 7;

    // https://regex101.com/r/GlgBdT/7
    // ie: "blog/2017-10-19_post-1.en.md" suffix is "en"
    // ie: "projet/1-projet-a.fr-FR.md" suffix is "fr-FR"
    private const SUFFIX_PATTERN = '(.*)\.' . Config::LANG_CODE_PATTERN;

    /**
     * Builds the prefix regex pattern from configured separators.
     *
     * @param string[] $separators Allowed separator characters between prefix and slug
     */
    private static function buildPrefixPattern(array $separators): string
    {
        $sepGroup = '(' . \implode('|', \array_map(fn (string $s): string => \preg_quote($s, '/'), $separators)) . ')';

        return self::PREFIX_BASE . $sepGroup . self::PREFIX_TAIL;
    }

    /**
     * Returns true if the string contains a prefix or a suffix.
     *
     * @param string[] $separators Prefix separators (used only for type "prefix")
     */
    protected static function has(string $string, string $type, array $separators = self::DEFAULT_SEPARATORS): bool
    {
        return (bool) \preg_match('/^' . self::getPattern($type, $separators) . '$/', $string);
    }

    /**
     * Returns true if the string contains a prefix.
     *
     * @param string[] $separators Allowed separator characters
     */
    public static function hasPrefix(string $string, array $separators = self::DEFAULT_SEPARATORS): bool
    {
        return self::matchPrefix($string, $matches, $separators);
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
     *
     * @param string[] $separators Prefix separators (used only for type "prefix")
     */
    protected static function get(string $string, string $type, array $separators = self::DEFAULT_SEPARATORS): ?string
    {
        if (self::has($string, $type, $separators)) {
            \preg_match('/^' . self::getPattern($type, $separators) . '$/', $string, $matches);
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
     * @param string[] $separators Allowed separator characters
     */
    public static function getPrefix(string $string, array $separators = self::DEFAULT_SEPARATORS): ?string
    {
        if (!self::matchPrefix($string, $matches, $separators)) {
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
     *
     * @param string[] $separators Allowed separator characters
     */
    public static function sub(string $string, array $separators = self::DEFAULT_SEPARATORS): string
    {
        if (self::hasPrefix($string, $separators)) {
            self::matchPrefix($string, $matches, $separators);
            $string = $matches[1] . $matches[self::PREFIX_SUFFIX_PART];
        }
        if (self::hasSuffix($string)) {
            \preg_match('/^' . self::getPattern('suffix') . '$/', $string, $matches);

            $string = $matches[1];
        }

        return $string;
    }

    /**
     * Returns string without the prefix (if exists).
     *
     * @param string[] $separators Allowed separator characters
     */
    public static function subPrefix(string $string, array $separators = self::DEFAULT_SEPARATORS): string
    {
        if (self::hasPrefix($string, $separators)) {
            self::matchPrefix($string, $matches, $separators);

            return $matches[1] . $matches[self::PREFIX_SUFFIX_PART];
        }

        return $string;
    }

    /**
     * Returns expreg pattern by $type.
     *
     * @param string[] $separators Prefix separators (used only for type "prefix")
     *
     * @throws \InvalidArgumentException
     */
    protected static function getPattern(string $type, array $separators = self::DEFAULT_SEPARATORS): string
    {
        switch ($type) {
            case 'prefix':
                return self::buildPrefixPattern($separators);
            case 'suffix':
                return self::SUFFIX_PATTERN;
            default:
                throw new \InvalidArgumentException('Argument must be "prefix" or "suffix".');
        }
    }

    /**
     * Matches string with prefix pattern.
     *
     * @param string     $string     String to test
     * @param array|null $matches    Output parameter populated with preg_match() matches
     * @param string[]   $separators Allowed separator characters
     *
     * @return bool True when the string matches the prefix pattern
     */
    private static function matchPrefix(string $string, ?array &$matches = null, array $separators = self::DEFAULT_SEPARATORS): bool
    {
        return (bool) \preg_match('/^' . self::buildPrefixPattern($separators) . '$/', $string, $matches);
    }
}

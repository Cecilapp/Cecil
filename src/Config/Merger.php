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

namespace Cecil\Config;

use Cecil\Config;

/**
 * Configuration merger.
 *
 * Merges configuration layers (defaults, themes, site), `$high` taking precedence over `$low`.
 */
final class Merger
{
    /**
     * Lists of items merged by identifier, instead of being concatenated.
     */
    private const array LIST_IDENTIFIERS = [
        'output.formats' => 'name',
    ];

    /**
     * Merges two configuration arrays, key by key: values of `$high` take precedence over values of `$low`.
     *
     * @param array<mixed>     $low  Lower priority configuration
     * @param array<mixed>     $high Higher priority configuration
     * @param Config::IMPORT_* $mode Import mode
     *
     * @return array<mixed>
     */
    public static function merge(array $low, array $high, int $mode, string $path = ''): array
    {
        foreach ($high as $key => $value) {
            if (!\array_key_exists($key, $low)) {
                $low[$key] = $value;
                continue;
            }
            $low[$key] = self::mergeValues($low[$key], $value, $mode, ltrim("$path.$key", '.'));
        }

        return $low;
    }

    /**
     * Merges two values, `$high` taking precedence over `$low`:
     * - scalars are replaced;
     * - a boolean applied to a map enables or disables it (ie: `cache: false`), the map is kept;
     * - lists of values are replaced as a whole, except in preserve mode (ie: theme configuration) where they are combined;
     * - a map of booleans applied to a list of values adds (`true`) or removes (`false`) items;
     * - lists of identified items (ie: `output.formats`) are merged by identifier;
     * - other lists of items (ie: menus entries) are concatenated in merge mode, replaced otherwise;
     * - maps are merged key by key.
     *
     * @param Config::IMPORT_* $mode Import mode
     */
    public static function mergeValues(mixed $low, mixed $high, int $mode, string $path): mixed
    {
        if (!\is_array($low) || !\is_array($high)) {
            return self::mergeScalar($low, $high);
        }
        if (array_is_list($low) && array_is_list($high)) {
            return self::mergeLists($low, $high, $mode, $path);
        }
        if (array_is_list($low) || array_is_list($high)) {
            return self::mergeListAndMap($low, $high);
        }

        return self::merge($low, $high, $mode, $path);
    }

    /**
     * Merges two values, at least one of them not being an array: `$high` replaces `$low`,
     * except a boolean applied to a map, which enables or disables it (ie: `cache: false`).
     */
    private static function mergeScalar(mixed $low, mixed $high): mixed
    {
        return \is_bool($high) && self::isMap($low) ? array_merge($low, ['enabled' => $high]) : $high;
    }

    /**
     * Merges two lists:
     * - lists of identified items (ie: `output.formats`) are merged by identifier;
     * - lists of values are replaced as a whole, except in preserve mode (ie: theme configuration) where they are combined;
     * - other lists of items (ie: menus entries) are concatenated in merge mode, replaced otherwise.
     *
     * @param list<mixed>      $low
     * @param list<mixed>      $high
     * @param Config::IMPORT_* $mode Import mode
     *
     * @return list<mixed>
     */
    private static function mergeLists(array $low, array $high, int $mode, string $path): array
    {
        if (isset(self::LIST_IDENTIFIERS[$path])) {
            return self::mergeListById($low, $high, self::LIST_IDENTIFIERS[$path]);
        }
        if (self::isValuesList($high) && self::isValuesList($low)) {
            // values required by a theme are kept: they can only be removed with a map of booleans
            return $mode === Config::IMPORT_PRESERVE ? array_values(array_unique(array_merge($high, $low))) : $high;
        }

        return $mode === Config::IMPORT_MERGE ? array_merge($low, $high) : $high;
    }

    /**
     * Merges a list and a map: a map of booleans applied to a list of values adds (`true`) or removes (`false`) items,
     * otherwise `$high` replaces `$low`, except an empty array (ie: `{}` in YAML) which doesn't override a map.
     *
     * @param array<mixed> $low
     * @param array<mixed> $high
     *
     * @return array<mixed>
     */
    private static function mergeListAndMap(array $low, array $high): array
    {
        if (self::isListPatch($high) && self::isValuesList($low)) {
            return self::applyListPatch($low, $high);
        }

        return $high === [] ? $low : $high;
    }

    /**
     * Is the value a non-empty map?
     *
     * @phpstan-assert-if-true non-empty-array<mixed> $value
     */
    private static function isMap(mixed $value): bool
    {
        return \is_array($value) && $value !== [] && !array_is_list($value);
    }

    /**
     * Merges two lists of items by identifier: items of `$high` override items of `$low` with the same identifier.
     *
     * @param list<mixed> $low
     * @param list<mixed> $high
     *
     * @return list<mixed>
     */
    private static function mergeListById(array $low, array $high, string $id): array
    {
        $ids = array_map(fn ($item) => \is_array($item) ? ($item[$id] ?? null) : null, $low);
        foreach ($high as $item) {
            $index = \is_array($item) && isset($item[$id]) ? array_search($item[$id], $ids, true) : false;
            if ($index === false || !\is_array($low[$index])) {
                $low[] = $item;
                continue;
            }
            $low[$index] = self::merge($low[$index], $item, Config::IMPORT_REPLACE);
        }

        return $low;
    }

    /**
     * Is the value a list of values (strings or integers)?
     *
     * @phpstan-assert-if-true list<string|int> $value
     */
    private static function isValuesList(mixed $value): bool
    {
        if (!\is_array($value) || !array_is_list($value)) {
            return false;
        }
        foreach ($value as $item) {
            if (!\is_string($item) && !\is_int($item)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Is the value a list patch (ie: `{rss: true, atom: false}`)?
     *
     * @phpstan-assert-if-true array<string|int, bool> $value
     */
    private static function isListPatch(mixed $value): bool
    {
        if (!\is_array($value) || array_is_list($value)) {
            return false;
        }
        foreach ($value as $item) {
            if (!\is_bool($item)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Applies a list patch to a list of values: adds items set to `true` and removes items set to `false`.
     *
     * @param list<string|int>         $list
     * @param array<string|int, bool> $patch
     *
     * @return list<string|int>
     */
    private static function applyListPatch(array $list, array $patch): array
    {
        $list = array_values(array_filter($list, fn ($item) => ($patch[$item] ?? null) !== false));
        $items = array_flip($list);
        foreach ($patch as $item => $enabled) {
            if ($enabled && !isset($items[$item])) {
                $list[] = $item;
            }
        }

        return $list;
    }
}

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

namespace Cecil\Util;

use Cecil\Exception\RuntimeException;
use Symfony\Component\String\Slugger\AsciiSlugger;

/**
 * Converts arbitrary paths/strings into URI-safe slugs.
 *
 * Preserves '.', '_', and '/' characters, handles non-ASCII (including CJK)
 * via the Symfony AsciiSlugger, and replaces any remaining unsafe characters
 * with dashes.
 */
class Slugifier
{
    /** @see https://regex101.com/r/... */
    public const SLUGIFY_PATTERN = '/(^\/|[^._a-z0-9\/]|-)+/';

    /** @var AsciiSlugger|null */
    private static $slugifier;

    /**
     * Turns a path (string) into a slug (URI).
     *
     * @throws RuntimeException
     */
    public static function slugify(string $path): string
    {
        if (!self::$slugifier instanceof AsciiSlugger) {
            self::$slugifier = new AsciiSlugger();
        }

        $placeholders = self::createSlugifyPlaceholders($path);
        $path = strtr($path, $placeholders);

        $path = preg_replace_callback('/[^\x00-\x7F]+/u', static function (array $matches): string {
            $locale = preg_match('/\p{Han}/u', $matches[0]) ? 'zh' : null;

            return self::$slugifier->slug($matches[0], '-', $locale)->lower()->toString();
        }, $path);
        if ($path === null) {
            throw new RuntimeException('Unable to slugify path.');
        }

        $path = preg_replace(self::SLUGIFY_PATTERN, '-', strtolower($path));
        if ($path === null) {
            throw new RuntimeException('Unable to slugify path.');
        }

        return ltrim(trim(strtr($path, array_flip($placeholders)), '-'), '/');
    }

    private static function createSlugifyPlaceholders(string $path): array
    {
        $placeholders = [];

        foreach (['.' => 'dot', '_' => 'underscore', '/' => 'slash'] as $character => $name) {
            $placeholders[$character] = self::createSlugifyPlaceholder($path, $name);
        }

        return $placeholders;
    }

    private static function createSlugifyPlaceholder(string $path, string $name): string
    {
        $index = 0;

        do {
            $placeholder = \sprintf('cecil%s%s', $name, substr(hash('sha256', $path . $name . $index), 0, 16));
            ++$index;
        } while (str_contains($path, $placeholder));

        return $placeholder;
    }
}

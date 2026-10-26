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

namespace Cecil\Converter;

use Cecil\Builder;
use Cecil\Exception\RuntimeException;

/**
 * Markdown parser factory.
 *
 * Creates the Markdown parser defined by the `pages.body.parser` configuration,
 * which must extend `Cecil\Converter\Parsedown` (e.g.: to add custom block or inline syntax).
 */
final class ParserFactory
{
    /**
     * Creates a Markdown parser.
     *
     * @param array<string, mixed>|null $options Parsedown options (e.g. 'selectors', 'language')
     *
     * @throws RuntimeException
     */
    public static function create(Builder $builder, ?array $options = null): Parsedown
    {
        $class = (string) ($builder->getConfig()->get('pages.body.parser') ?? Parsedown::class);
        if (!is_a($class, Parsedown::class, true)) {
            throw new RuntimeException(\sprintf('Configuration: the Markdown parser class "%s" (`pages.body.parser`) must exist and extend "%s".', $class, Parsedown::class));
        }

        return new $class($builder, $options);
    }
}

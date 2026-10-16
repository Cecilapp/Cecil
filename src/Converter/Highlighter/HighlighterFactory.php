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

namespace Cecil\Converter\Highlighter;

use Cecil\Config;
use Cecil\Exception\ConfigException;

/**
 * Highlighter factory.
 *
 * Creates the highlighter defined by the `pages.body.highlight.engine` configuration option.
 * Instances are shared, as highlighters are stateless and costly to create.
 */
class HighlighterFactory
{
    public const string DEFAULT_ENGINE = 'highlightjs';

    /** @var array<string, class-string<HighlighterInterface>> Available engines */
    public const array ENGINES = [
        'highlightjs' => HighlightJs::class,
        'tempest'     => Tempest::class,
    ];

    /** @var array<string, HighlighterInterface> */
    protected static array $instances = [];

    /**
     * Returns the highlighter of the configured engine.
     *
     * @throws ConfigException
     */
    public static function create(Config $config): HighlighterInterface
    {
        $engine = $config->get('pages.body.highlight.engine') ?? self::DEFAULT_ENGINE;
        if (!\is_string($engine) || !isset(self::ENGINES[$engine])) {
            throw new ConfigException(\sprintf(
                'Option "pages.body.highlight.engine" must be one of: "%s".',
                implode('", "', array_keys(self::ENGINES))
            ));
        }

        return static::$instances[$engine] ??= new (self::ENGINES[$engine])();
    }
}

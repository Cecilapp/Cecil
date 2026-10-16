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

/**
 * Highlighter interface.
 *
 * Abstracts the code syntax highlighting library, so it can be changed
 * with the `pages.body.highlight.engine` configuration option.
 */
interface HighlighterInterface
{
    /**
     * Highlights a code snippet.
     *
     * @throws \Cecil\Exception\RuntimeException If the language is not supported
     */
    public function highlight(string $code, string $language): Highlighted;
}

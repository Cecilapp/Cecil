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
 * Highlighted code.
 *
 * Result of a code highlighting: the highlighted HTML and the CSS classes
 * to add to the `<code>` element (used by highlighter themes).
 */
final readonly class Highlighted
{
    public function __construct(
        public string $html,
        public string $class = '',
    ) {
    }
}

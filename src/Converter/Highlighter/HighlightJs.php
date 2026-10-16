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

use Cecil\Exception\RuntimeException;
use Highlight\Highlighter;

/**
 * Highlight.js highlighter.
 *
 * Uses scrivo/highlight.php, a PHP port of highlight.js:
 * output is compatible with highlight.js themes (`hljs-*` CSS classes).
 */
class HighlightJs implements HighlighterInterface
{
    /** Costly to create (registerAllLanguages() scans 185 files): instance is shared by the factory */
    protected Highlighter $highlighter;

    public function __construct()
    {
        $this->highlighter = new Highlighter();
    }

    #[\Override]
    public function highlight(string $code, string $language): Highlighted
    {
        try {
            $highlighted = $this->highlighter->highlight($language, $code);
        } catch (\Exception $e) {
            throw new RuntimeException($e->getMessage(), previous: $e);
        }

        return new Highlighted($highlighted->value, 'hljs ' . $highlighted->language);
    }
}

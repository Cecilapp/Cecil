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
use Tempest\Highlight\Highlighter;

/**
 * Tempest highlighter.
 *
 * Uses tempest/highlight, a fast server-side highlighter:
 * output is compatible with tempest/highlight themes (`hl-*` CSS classes).
 */
class Tempest implements HighlighterInterface
{
    protected Highlighter $highlighter;

    /** @var array<string, bool> Supported languages names and aliases */
    protected array $languages;

    public function __construct()
    {
        $this->highlighter = new Highlighter();
        // tempest/highlight silently falls back to plain text for unknown languages
        $this->languages = array_fill_keys(array_keys((fn () => $this->languages)->call($this->highlighter)), true);
    }

    #[\Override]
    public function highlight(string $code, string $language): Highlighted
    {
        if (!isset($this->languages[$language])) {
            throw new RuntimeException(\sprintf('Unknown language: "%s".', $language));
        }

        return new Highlighted($this->highlighter->parse($code, $language), 'hl');
    }
}

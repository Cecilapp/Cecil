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

namespace Cecil\Renderer\PostProcessor;

use Cecil\Collection\Page\Page;

/**
 * HtmlExcerpt class.
 *
 * This class processes HTML output to insert an excerpt marker
 * at the first occurrence of an HTML comment indicating an excerpt or break.
 * The marker is inserted as a `<span id="more"></span>` element.
 */
class HtmlExcerpt extends AbstractPostProcessor
{
    /**
     * {@inheritdoc}
     */
    public function process(Page $page, string $output, string $format): string
    {
        if ($format == 'html') {
            // https://regex101.com/r/Xl7d5I/3
            $pattern = '(.*)(<!--[[:blank:]]?(excerpt|break)[[:blank:]]?-->)(.*)';
            $replacement = '$1<span id="more"></span>$4';
            $excerpt = preg_replace('/' . $pattern . '/is', $replacement, $output, 1);
            $output = $excerpt ?? $output;
        }

        return $output;
    }
}

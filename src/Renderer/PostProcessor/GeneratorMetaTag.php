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

use Cecil\Builder;
use Cecil\Collection\Page\Page;

/**
 * GeneratorMetaTag class.
 *
 * This post-processor adds a meta tag in the HTML head section
 * to indicate the version of Cecil used to generate the site.
 * The tag is only added if it does not already exist.
 */
class GeneratorMetaTag extends AbstractPostProcessor
{
    /**
     * {@inheritdoc}
     *
     * Adds generator meta tag.
     */
    public function process(Page $page, string $output, string $format): string
    {
        if ($format == 'html') {
            if (!preg_match('/<meta name="generator".*/i', $output)) {
                $meta = \sprintf('<meta name="generator" content="Cecil %s">', Builder::getVersion());
                $output = preg_replace_callback('/([[:blank:]]*)(<\/head>)/i', function ($matches) use ($meta) {
                    return str_repeat($matches[1] ?: ' ', 2) . $meta . "\n" . $matches[1] . $matches[2];
                }, $output);
            }
        }

        return $output;
    }
}

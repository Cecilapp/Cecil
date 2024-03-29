<?php

declare(strict_types=1);

/*
 * This file is part of Cecil.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Renderer\PostProcessor;

use Cecil\Collection\Page\Page;

/**
 * HtmlExcerpt class.
 *
 * Replaces excerpt or break tag by HTML anchor.
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

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
use Cecil\Collection\Page\PrefixSuffix;

/**
 * MarkdownLink class.
 */
class MarkdownLink extends AbstractPostProcessor
{
    /**
     * {@inheritdoc}
     *
     * Replaces internal link to *.md files with the right URL.
     */
    public function process(Page $page, string $output, string $format): string
    {
        $output = preg_replace_callback(
            // https://regex101.com/r/ycWMe4/1
            '/href="(\/|)([A-Za-z0-9_\.\-\/]+)\.md(\#[A-Za-z0-9_\-]+)?"/is',
            function ($matches) use ($page) {
                // section spage
                $hrefPattern = 'href="../%s/%s"';
                // root page
                if (empty($page->getFolder())) {
                    $hrefPattern = 'href="%s/%s"';
                }
                // root link
                if ($matches[1] == '/') {
                    $hrefPattern = 'href="/%s/%s"';
                }

                return sprintf($hrefPattern, Page::slugify(PrefixSuffix::sub($matches[2])), $matches[3] ?? '');
            },
            $output
        );

        return $output;
    }
}

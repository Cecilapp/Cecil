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
use Cecil\Url;
use Cecil\Util;

/**
 * MarkdownLink class.
 *
 * This class processes Markdown links in the output HTML,
 * replacing internal links to `.md` files with the correct URLs.
 * Relative links are resolved from the folder of the source file (like on GitHub),
 * then the URL of the targeted page is used (if it exists).
 * It handles links that may include a section anchor and adjusts the href attribute accordingly.
 */
class MarkdownLink extends AbstractPostProcessor
{
    /**
     * {@inheritdoc}
     *
     * Replaces internal link to *.md files with the right URL.
     */
    #[\Override]
    public function process(Page $page, string $output, string $format): string
    {
        $output = preg_replace_callback(
            // https://regex101.com/r/ycWMe4/1
            '/href="(\/|)([A-Za-z0-9_\.\-\/]+)\.md(\#[A-Za-z0-9_\-]+)?"/is',
            function ($matches) use ($page) {
                $path = $matches[2] . '.md';
                // relative link: resolved from the folder of the source file
                if ($matches[1] != '/') {
                    $path = Util::joinPath($this->getSourceFolder($page), $path);
                }
                // ignores parent folders beyond the pages directory
                $path = (string) preg_replace('/^(\.\.\/)+/', '', $path);

                return \sprintf('href="%s%s"', $this->getUrl($page, Page::createIdFromPath($path)), $matches[3] ?? '');
            },
            $output
        ) ?? $output;

        return $output;
    }

    /**
     * Returns the folder of the page source file, relative to the pages directory.
     */
    private function getSourceFolder(Page $page): string
    {
        if (empty($filepath = $page->getVariable('filepath'))) {
            return (string) $page->getFolder();
        }
        $folder = \dirname(str_replace('\\', '/', (string) $filepath));

        return $folder == '.' ? '' : $folder;
    }

    /**
     * Returns the URL of the targeted page, preferring the page in the current language.
     */
    private function getUrl(Page $page, string $pageId): string
    {
        $pages = $this->builder->getPages();
        $language = (string) $page->getVariable('language', $this->config->getLanguageDefault());
        if ($language != $this->config->getLanguageDefault() && $pages->has("$language/$pageId")) {
            $pageId = "$language/$pageId";
        }
        if ($pages->has($pageId)) {
            return (string) new Url($this->builder, $pages->get($pageId));
        }

        // page not found: builds the URL from the page ID
        return rtrim((string) new Url($this->builder, $pageId), '/') . '/';
    }
}

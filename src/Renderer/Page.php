<?php
/**
 * This file is part of the Cecil/Cecil package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Renderer;

use Cecil\Collection\Page\Page as PageItem;
use Cecil\Config;

/**
 * Class Renderer\Page.
 */
class Page
{
    /** @var Config */
    protected $config;

    /**
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Returns the path to the output (rendered) file.
     *
     * Use cases:
     * - default: path + suffix + extension (ie: blog/post-1/index.html)
     * - subpath: path + subpath + suffix + extension (ie: blog/post-1/amp/index.html)
     * - ugly: path + extension (ie: 404.html, sitemap.xml, robots.txt)
     * - path only (ie: _redirects)
     * - l10n: language + path + suffix + extension (ie: fr/blog/page/index.html)
     *
     * @param PageItem $page
     * @param string   $format Output format (ie: html, amp, json, etc.)
     *
     * @return string
     */
    public function getOutputFile(PageItem $page, string $format): string
    {
        $path = $page->getPath();
        $subpath = '';
        $suffix = '/index';
        $extension = 'html';
        $uglyurl = (bool) $page->getVariable('uglyurl');
        $language = $page->getVariable('language');
        // site config
        $subpath = (string) $this->config->getOutputFormatProperty($format, 'subpath');
        $suffix = (string) $this->config->getOutputFormatProperty($format, 'suffix');
        $extension = (string) $this->config->getOutputFormatProperty($format, 'extension');
        // if ugly URL: not suffix
        if ($uglyurl) {
            $suffix = null;
        }
        // add extension
        if ($extension) {
            $extension = \sprintf('.%s', $extension);
        }
        // homepage special case: path = 'index'
        if (empty($path) && empty($suffix)) {
            $path = 'index';
        }
        // do not prefix URL for default language
        if ($language == $this->config->getLanguageDefault()) {
            $language = '';
        }

        return \Cecil\Util::joinPath($language, $path, $subpath, $suffix).$extension;
    }

    /**
     * Returns the public URL.
     *
     * @param PageItem $page
     * @param string   $format Output format (ie: html, amp, json, etc.)
     *
     * @return string
     */
    public function getUrl(PageItem $page, string $format = 'html'): string
    {
        $uglyurl = $page->getVariable('uglyurl') ? true : false;
        $output = $this->getOutputFile($page, $format);

        if (!$uglyurl) {
            $output = str_replace('index.html', '', $output);
        }

        return $output;
    }
}

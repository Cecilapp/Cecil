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

namespace Cecil\Renderer;

use Cecil\Builder;
use Cecil\Collection\Page\Page as PageItem;

/**
 * Page renderer class.
 *
 * This class is responsible for generating the output file path and URL for a page
 * based on the output format properties defined in the configuration.
 * It handles various scenarios such as ugly URLs, multilingual support, and subpaths.
 */
class Page
{
    /**
     * Builder object.
     * @var Builder
     */
    protected $builder;
    /**
     * Configuration object.
     * @var \Cecil\Config
     */
    protected $config;
    /**
     * Page item.
     * @var PageItem
     */
    protected $page;

    public function __construct(Builder $builder, PageItem $page)
    {
        $this->builder = $builder;
        $this->config = $this->builder->getConfig();
        $this->page = $page;
    }

    /**
     * Returns the path of the rendered page, based on the output format properties.
     * Use cases:
     *   - default: path + filename + extension (e.g.: 'blog/post-1/index.html')
     *   - with subpath: path + subpath + filename + extension (e.g.: 'blog/post-1/amp/index.html')
     *   - ugly URL: path + extension (e.g.: '404.html', 'sitemap.xml', 'robots.txt')
     *   - path only (e.g.: '_redirects')
     *   - i18n: language code + default (e.g.: 'fr/blog/page/index.html')
     *
     * @param string $format Output format (ie: 'html', 'amp', 'json', etc.)
     */
    public function getOutputFilePath(string $format): string
    {
        $path = $this->page->getPath();
        $subpath = (string) $this->config->getOutputFormatProperty($format, 'subpath');
        $filename = (string) $this->config->getOutputFormatProperty($format, 'filename');
        $extension = (string) $this->config->getOutputFormatProperty($format, 'extension');
        $uglyurl = (bool) $this->page->getVariable('uglyurl');
        $language = $this->page->getVariable('language');
        // is ugly URL?
        if ($uglyurl) {
            $filename = '';
        }
        // add extension if exists
        if ($extension) {
            $extension = \sprintf('.%s', $extension);
        }
        // homepage special case (need "index")
        if (empty($path) && empty($filename)) {
            $path = 'index';
        }
        // do not prefix path with language code for the default language
        if ($language === null || ($language == $this->config->getLanguageDefault() && !$this->config->isEnabled('language.prefix'))) {
            $language = '';
        }
        // do not prefix "not multilingual" virtual pages
        if ($this->page->getVariable('multilingual') === false) {
            $language = '';
        }

        return \Cecil\Util::joinPath($language, $path, $subpath, $filename) . $extension;
    }

    /**
     * Returns the public path of the page.
     *
     * @param string $format Output format (ie: 'html', 'amp', 'json', etc.), 'html' by default
     */
    public function getPath(string $format = 'html'): string
    {
        $output = $this->getOutputFilePath($format);

        // if `uglyurl` is true do not remove "index.html" from the path
        if ($this->page->getVariable('uglyurl')) {
            return $output;
        }

        return str_replace('index.html', '', $output);
    }
}

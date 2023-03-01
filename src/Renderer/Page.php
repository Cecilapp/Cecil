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

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Returns the path of the rendered file, based on the output format properties.
     *
     * Use cases:
     *   - default: path + filename + extension (e.g.: 'blog/post-1/index.html')
     *   - subpath: path + subpath + filename + extension (e.g.: 'blog/post-1/amp/index.html')
     *   - ugly: path + extension (e.g.: '404.html', 'sitemap.xml', 'robots.txt')
     *   - path only (e.g.: '_redirects')
     *   - language (e.g.: 'fr/blog/page/index.html')
     *
     * @param PageItem $page
     * @param string   $format Output format (ie: 'html', 'amp', 'json', etc.)
     */
    public function getOutputFile(PageItem $page, string $format): string
    {
        $path = $page->getPath();
        $subpath = (string) $this->config->getOutputFormatProperty($format, 'subpath');
        $filename = (string) $this->config->getOutputFormatProperty($format, 'filename');
        $extension = (string) $this->config->getOutputFormatProperty($format, 'extension');
        $uglyurl = (bool) $page->getVariable('uglyurl');
        $language = $page->getVariable('language');
        // if ugly URL
        if ($uglyurl) {
            $filename = '';
        }
        // add extension if exists
        if ($extension) {
            $extension = \sprintf('.%s', $extension);
        }
        // homepage special case
        if (empty($path) && empty($filename)) {
            $path = 'index';
        }
        // do not prefix URL for the default language
        if ($language == $this->config->getLanguageDefault() || $language === null) {
            $language = '';
        }

        return \Cecil\Util::joinPath($language, $path, $subpath, $filename) . $extension;
    }

    /**
     * Returns the public URL.
     *
     * @param PageItem $page
     * @param string   $format Output format (ie: 'html', 'amp', 'json', etc.), 'html' by default
     */
    public function getUrl(PageItem $page, string $format = 'html'): string
    {
        $output = $this->getOutputFile($page, $format);
        if (!($page->getVariable('uglyurl') ?? false)) {
            $output = str_replace('index.html', '', $output);
        }

        return $output;
    }
}

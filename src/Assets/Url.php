<?php
/**
 * This file is part of the Cecil/Cecil package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Assets;

use Cecil\Builder;
use Cecil\Collection\Page\Page;
use Cecil\Config;
use Cecil\Renderer\Page as PageRenderer;
use Cecil\Util;
use Cocur\Slugify\Slugify;

class Url
{
    /** @var Builder */
    protected $builder;
    /** @var Config */
    protected $config;
    /** @var Page|Asset|string|null */
    protected $value;
    /** @var array */
    protected $options;
    /** @var string */
    protected $baseurl;
    /** @var string */
    protected $url;
    /** @var Slugify */
    private static $slugifier;

    /**
     * Creates an URL from string, Page or Asset.
     *
     * $options[
     *     'canonical' => true,
     *     'format'    => 'json',
     * ];
     *
     * @param Builder                $builder
     * @param Page|Asset|string|null $value
     * @param array|null             $options
     */
    public function __construct(Builder $builder, $value, array $options = null)
    {
        $this->builder = $builder;
        $this->config = $builder->getConfig();
        if (!self::$slugifier instanceof Slugify) {
            self::$slugifier = Slugify::create(['regexp' => Page::SLUGIFY_PATTERN]);
        }
        $this->baseurl = (string) $this->config->get('baseurl');

        // handles options
        $canonical = null;
        $format = null;
        $lang = $this->config->getLanguageDefault();
        extract(is_array($options) ? $options : [], EXTR_IF_EXISTS);

        // canonical URL?
        $base = '';
        if ((bool) $this->config->get('canonicalurl') || $canonical === true) {
            $base = rtrim($this->baseurl, '/');
        }
        if ($canonical === false) {
            $base = '';
        }

        // prefix path with a specific language?
        if ($lang != $this->config->getLanguageDefault()) {
            $value = sprintf('%s/%s%s', $lang, $value, !empty($value) ? '/' : '');
        }

        // value is empty (ie: `url()`)
        if (is_null($value) || empty($value) || $value == '/') {
            $this->url = $base.'/';

            return;
        }

        switch (true) {
            // Page
            case $value instanceof Page:
                if (!$format) {
                    $format = $value->getVariable('output');
                    if (is_array($value->getVariable('output'))) {
                        $format = $value->getVariable('output')[0];
                    }
                    if (!$format) {
                        $format = 'html';
                    }
                }
                $this->url = $base.'/'.ltrim((new PageRenderer($this->config))->getUrl($value, $format), '/');
                break;
            // Asset
            case $value instanceof Asset:
                $asset = $value;
                $this->url = $base.'/'.ltrim($asset['path'], '/');
                break;
            // string
            case is_string($value):
                // potential Page ID
                $pageId = self::$slugifier->slugify($value);
                switch (true) {
                    // External URL
                    case Util\Url::isUrl($value):
                        $this->url = $value;
                        break;
                    // Page ID as string
                    case $this->builder->getPages()->has($pageId):
                        $page = $this->builder->getPages()->get($pageId);
                        $this->url = new self($this->builder, $page, $options);
                        break;
                    // asset as string
                    case false !== strpos($value, '.') ? true : false:
                        $this->url = $base.'/'.ltrim($value, '/');
                        break;
                    // others cases?
                    default:
                        // others cases
                        $this->url = $base.'/'.$value;
                }
        }
    }

    /**
     * Returns URL.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->url;
    }

    /**
     * Returns URL.
     *
     * @return string
     */
    public function getUrl(): string
    {
        return $this->__toString();
    }
}

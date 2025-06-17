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

namespace Cecil;

use Cecil\Assets\Asset;
use Cecil\Builder;
use Cecil\Collection\Menu\Entry as MenuEntry;
use Cecil\Collection\Page\Page;
use Cecil\Config;
use Cecil\Renderer\Page as PageRenderer;
use Cecil\Util;
use Cocur\Slugify\Slugify;

/**
 * URL class.
 *
 * Builds an URL from a Page, a Menu Entry, an Asset or a string.
 */
class Url
{
    /** @var Builder */
    protected $builder;

    /** @var Config */
    protected $config;

    /** @var string */
    protected $url;

    /** @var Page Slugifier */
    private static $slugifier;

    /**
     * Creates an URL from a Page, a Menu Entry, an Asset or a string.
     *
     * @param Builder                          $builder
     * @param Page|MenuEntry|Asset|string|null $value
     * @param array|null                       $options Rendering options, e.g.: ['canonical' => true, 'format' => 'html', 'language' => 'fr']
     */
    public function __construct(Builder $builder, $value, ?array $options = null)
    {
        $this->builder = $builder;
        $this->config = $builder->getConfig();
        if (!self::$slugifier instanceof Slugify) {
            self::$slugifier = Slugify::create(['regexp' => Page::SLUGIFY_PATTERN]);
        }

        // handles options
        $canonical = null; // if true prefix url with baseurl config
        $format = null;    // output format
        $language = null;  // force language
        extract(\is_array($options) ? $options : [], EXTR_IF_EXISTS);

        // canonical URL?
        $base = '';
        if ($this->config->isEnabled('canonicalurl') || $canonical === true) {
            $base = rtrim((string) $this->config->get('baseurl'), '/');
        }
        if ($canonical === false) {
            $base = '';
        }

        // if value is empty (i.e.: `url()`) returns home URL
        if (\is_null($value) || empty($value) || $value == '/') {
            $this->url = $base . '/';

            return;
        }

        switch (true) {
            case $value instanceof Page: // $value is a Page
                /** @var Page $value */
                if (!$format) {
                    $format = $value->getVariable('output');
                    if (\is_array($value->getVariable('output'))) {
                        $default = array_search('html', $value->getVariable('output')) ?: 0;
                        $format = $value->getVariable('output')[$default];
                    }
                    if (!$format) {
                        $format = 'html';
                    }
                }
                $this->url = $base . '/' . ltrim((new PageRenderer($this->config))->getUrl($value, $format), '/');
                if ($canonical && $value->hasVariable('canonical') && $value->getVariable('canonical')['url']) { // canonical URL
                    $this->url = $value->getVariable('canonical')['url'];
                }
                break;
            case $value instanceof MenuEntry: // $value is a Menu Entry
                /** @var MenuEntry $value */
                if (Util\File::isRemote($value['url'])) {
                    $this->url = $value['url'];
                    break;
                }
                $this->url = $base . '/' . ltrim($value['url'], '/');
                break;
            case $value instanceof Asset: // $value is an Asset
                /** @var Asset $value */
                $this->url = $base . '/' . ltrim($value['path'], '/');
                if ($value->isImageInCdn()) {
                    $this->url = (string) $value;
                }
                break;
            case \is_string($value): // others cases
                /** @var string $value */
                // $value is a potential Page ID
                $pageId = self::$slugifier->slugify($value);
                // should force language?
                $lang = '';
                if ($language !== null && $language != $this->config->getLanguageDefault()) {
                    $pageId = "$language/$pageId";
                    $lang = "$language/";
                }
                switch (true) {
                    case Util\File::isRemote($value): // $value is an external URL
                        $this->url = $value;
                        break;
                    case $this->builder->getPages()->has($pageId): // $pageId exists in pages collection
                        $this->url = (string) new self($this->builder, $this->builder->getPages()->get($pageId), $options);
                        break;
                    default:
                        // remove double language prefix
                        if ($lang && Util\Str::startsWith($value, $lang)) {
                            $value = substr($value, \strlen($lang));
                        }
                        $this->url = $base . '/' . $lang . ltrim($value, '/');
                }
        }
    }

    /**
     * If called like a string returns built URL.
     */
    public function __toString(): string
    {
        return $this->getUrl();
    }

    /**
     * Returns built URL.
     */
    public function getUrl(): string
    {
        return (string) $this->url;
    }
}

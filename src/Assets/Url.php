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
    protected $version;

    /** @var bool */
    protected $canonical;
    /** @var bool */
    protected $addhash;
    /** @var string */
    protected $format;

    /** @var Slugify */
    private static $slugifier;

    /**
     * @param Builder $builder
     */
    public function __construct(Builder $builder, $value, array $options)
    {
        $this->builder = $builder;
        $this->config = $builder->getConfig();
        if (!self::$slugifier instanceof Slugify) {
            self::$slugifier = Slugify::create(['regexp' => Page::SLUGIFY_PATTERN]);
        }
        $this->baseurl = (string) $this->config->get('baseurl');
        $this->version = (string) $this->builder->time;

        switch ($value) {
            case 'value':
                // code...
                break;

            default:
                // code...
                break;
        }
    }

    /**
     * Creates an URL.
     *
     * $options[
     *     'canonical' => true,
     *     'addhash'   => false,
     *     'format'    => 'json',
     * ];
     *
     * @param Page|Asset|string|null $value
     * @param array|null             $options
     *
     * @return mixed
     */
    public function createUrl($value = null, $options = null)
    {
        $base = '';

        // handles options
        $canonical = null;
        $addhash = false;
        $format = null;
        extract(is_array($options) ? $options : [], EXTR_IF_EXISTS);

        // canonicalurl?
        if ((bool) $this->config->get('canonicalurl') || $canonical === true) {
            $base = rtrim($this->baseurl, '/');
        }
        if ($canonical === false) {
            $base = '';
        }

        // value is empty: url()
        if (empty($value) || $value == '/') {
            return $base.'/';
        }

        // value is a Page item
        if ($value instanceof Page) {
            if (!$format) {
                $format = $value->getVariable('output');
                if (is_array($value->getVariable('output'))) {
                    $format = $value->getVariable('output')[0];
                }
                if (!$format) {
                    $format = 'html';
                }
            }
            $url = $value->getUrl($format, $this->config);
            $url = $base.'/'.ltrim($url, '/');

            return $url;
        }

        // value is an Asset object
        if ($value instanceof Asset) {
            $asset = $value;
            $url = $asset['path'];
            if ($addhash) {
                $url .= '?'.$this->version;
            }
            $url = $base.'/'.ltrim($url, '/');
            $asset['path'] = $url;

            return $asset;
        }

        // value is an external URL
        if (Util::isExternalUrl($value)) {
            $url = $value;

            return $url;
        }

        // value is a string
        $value = Util::joinPath($value);

        // DEBUG
        dump($value);
        dump(strrpos($value, '.'));

        // value is (certainly) a path to a ressource (ie: 'path/file.pdf')
        if (false !== strpos($value, '.')) {
            $url = $value;
            if ($addhash) {
                $url .= '?'.$this->version;
            }
            $url = $base.'/'.ltrim($url, '/');

            return $url;
        }

        // others cases
        $url = $base.'/'.$value;

        // value is a page ID (ie: 'path/my-page')
        try {
            $pageId = self::$slugifier->slugify($value);
            $page = $this->builder->getPages()->get($pageId);
            $url = $this->createUrl($page, $options);
        } catch (\DomainException $e) {
            // nothing to do
        }

        return $url;
    }

    public function isCanonical(array $options)
    {
        if ((bool) $this->config->get('canonicalurl') || $canonical === true) {
            return true;
        }

        return false;
    }

    public function extractOptions()
    {
        $canonical = null;
        $addhash = false;
        $format = null;
        extract(is_array($options) ? $options : [], EXTR_IF_EXISTS);
    }
}

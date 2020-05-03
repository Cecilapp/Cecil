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
use Cecil\Config;
use Cecil\Exception\Exception;
use Cecil\Util;

class Asset implements \ArrayAccess
{
    /** @var Builder */
    protected $builder;
    /** @var Config */
    protected $config;
    /** @var array */
    protected $properties = [];

    /**
     * Loads a file.
     *
     * @param Builder
     * @param string     $path
     * @param array|null $options
     */
    public function __construct(Builder $builder, string $path, array $options = null)
    {
        $this->builder = $builder;
        $this->config = $builder->getConfig();

        if (false === $filePath = $this->findFile($path)) {
            throw new Exception(sprintf('Asset file "%s" doesn\'t exist.', $path));
        }

        // handles options
        $canonical = null;
        $attributs = null;
        extract(is_array($options) ? $options : [], EXTR_IF_EXISTS);
        // url
        $baseurl = (string) $this->config->get('baseurl');
        $base = '';
        if ((bool) $this->config->get('canonicalurl') || $canonical === true) {
            $base = rtrim($baseurl, '/');
        }
        if ($canonical === false) {
            $base = '';
        }

        // prepares properties
        $this->properties['file'] = $filePath;
        $this->properties['path'] = '/'.ltrim($path, '/');
        $this->properties['url'] = $base.'/'.ltrim($path, '/');
        $this->properties['ext'] = pathinfo($filePath, PATHINFO_EXTENSION);
        $this->properties['type'] = explode('/', mime_content_type($filePath))[0];
        $this->properties['content'] = null;
        if ($this->properties['type'] == 'text') {
            $this->properties['content'] = file_get_contents($filePath);
        }
        $this->properties['attributs'] = $attributs;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->properties['path'];
    }

    /**
     * Implements \ArrayAccess.
     */
    public function offsetSet($offset, $value)
    {
        if (!is_null($offset)) {
            $this->properties[$offset] = $value;
        }
    }

    /**
     * Implements \ArrayAccess.
     */
    public function offsetExists($offset)
    {
        return isset($this->properties[$offset]);
    }

    /**
     * Implements \ArrayAccess.
     */
    public function offsetUnset($offset)
    {
        unset($this->properties[$offset]);
    }

    /**
     * Implements \ArrayAccess.
     */
    public function offsetGet($offset)
    {
        return isset($this->properties[$offset]) ? $this->properties[$offset] : null;
    }

    /**
     * Returns as HTML tag.
     *
     * @return string
     */
    public function getHtml(): string
    {
        if ($this->properties['type'] == 'image') {
            $title = array_key_exists('title', $this->properties['attributs']) ? $this->properties['attributs']['title'] : null;
            $alt = array_key_exists('alt', $this->properties['attributs']) ? $this->properties['attributs']['alt'] : null;

            return \sprintf(
                '<img src="%s"%s%s>',
                $this->properties['path'],
                !is_null($title) ? \sprintf(' title="%s"', $title) : '',
                !is_null($alt) ? \sprintf(' alt="%s"', $alt) : ''
            );
        }

        switch ($this->properties['ext']) {
            case 'css':
                return \sprintf('<link rel="stylesheet" href="%s">', $this->properties['path']);
            case 'js':
                return \sprintf('<script src="%s"></script>', $this->properties['path']);
        }

        throw new Exception(\sprintf('%s is available only with CSS, JS and images files.', '.html'));
    }

    /**
     * Returns file's content.
     *
     * @return string
     */
    public function getInline(): string
    {
        if (!array_key_exists('content', $this->properties)) {
            throw new Exception(\sprintf('%s is available only with CSS et JS files.', '.inline'));
        }

        return $this->properties['content'];
    }

    /**
     * Try to find a static file (in site or theme(s)) if exists or returns false.
     *
     * @param string $path
     *
     * @return string|false
     */
    private function findFile(string $path)
    {
        $filePath = Util::joinFile($this->config->getStaticPath(), $path);
        if (Util::getFS()->exists($filePath)) {
            return $filePath;
        }

        // checks in each theme
        foreach ($this->config->getTheme() as $theme) {
            $filePath = Util::joinFile($this->config->getThemeDirPath($theme, 'static'), $path);
            if (Util::getFS()->exists($filePath)) {
                return $filePath;
            }
        }

        return false;
    }
}

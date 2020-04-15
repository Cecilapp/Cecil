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
use Cecil\Exception\Exception;
use Cecil\Util;

class Asset
{
    /** @var Builder */
    protected $builder;
    /** @var Config */
    protected $config;
    /** @var array */
    protected $properties = [];

    const CACHE_ASSETS_DIR = 'assets';

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

        if (false === $filePath = $this->getFile($path)) {
            throw new Exception(sprintf('Asset file "%s" doesn\'t exist.', $path));
        }
        $fileInfo = new \SplFileInfo($filePath);

        $baseurl = (string) $this->config->get('baseurl');
        $base = '';

        // handles options
        $canonical = null;
        $attributs = null;
        extract(is_array($options) ? $options : [], EXTR_IF_EXISTS);

        // prepares properties
        $this->properties['path'] = '/'.ltrim($path, '/');
        if ((bool) $this->config->get('canonicalurl') || $canonical === true) {
            $base = rtrim($baseurl, '/');
        }
        if ($canonical === false) {
            $base = '';
        }
        $this->properties['url'] = $base.'/'.ltrim($path, '/');
        $this->properties['ext'] = $fileInfo->getExtension();
        $this->properties['type'] = explode('/', mime_content_type($fileInfo->getPathname()))[0];
        if ($this->properties['type'] == 'text') {
            $this->properties['content'] = file_get_contents($fileInfo->getPathname());
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
     * Returns as HTML tag.
     *
     * @return string
     */
    public function getHtml(): string
    {
        if ($this->properties['type'] == 'image') {
            return \sprintf(
                '<img src="%s" title="%s" alt="%s">',
                $this->properties['path'],
                $this->properties['attributs']['title'],
                $this->properties['attributs']['alt']
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
     * Get a static file (in site or theme(s)) if exists or false.
     *
     * @param string $path
     *
     * @return string|false
     */
    private function getFile(string $path)
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

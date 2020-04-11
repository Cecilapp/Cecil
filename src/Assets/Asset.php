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

class Asset extends AbstractAsset
{
    protected $asset = [];
    protected $fileLoaded = false;

    /**
     * Loads a file.
     *
     * @param Builder
     * @param string     $path
     * @param array|null $options
     */
    public function __construct(Builder $builder, string $path, array $options = null)
    {
        parent::__construct($builder);

        if (false === $filePath = $this->getFile($path)) {
            throw new Exception(sprintf('Asset file "%s" doesn\'t exist.', $path));
        }
        $fileInfo = new \SplFileInfo($filePath);

        $baseurl = (string) $this->config->get('baseurl');
        $base = '';

        // handles options
        $canonical = null;
        $attributs = null;
        extract(is_array($options) ? $options : []);
        if ((bool) $this->config->get('canonicalurl') || $canonical === true) {
            $base = rtrim($baseurl, '/');
        }
        if ($canonical === false) {
            $base = '';
        }

        // prepares properties
        $this->asset['path'] = $base.'/'.ltrim($path, '/');
        $this->asset['ext'] = $fileInfo->getExtension();
        $this->asset['type'] = explode('/', mime_content_type($fileInfo->getPathname()))[0];
        if ($this->asset['type'] == 'text') {
            $this->asset['content'] = file_get_contents($fileInfo->getPathname());
        }
        $this->asset['attributs'] = $attributs;
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

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->asset['path'];
    }

    /**
     * Returns as HTML tag.
     *
     * @return string
     */
    public function getHtml(): string
    {
        if ($this->asset['type'] == 'image') {
            return \sprintf(
                '<img src="%s" title="%s" alt="%s">',
                $this->asset['path'],
                $this->asset['attributs']['title'],
                $this->asset['attributs']['alt']
            );
        }

        switch ($this->asset['ext']) {
            case 'css':
                return \sprintf('<link rel="stylesheet" href="%s">', $this->asset['path']);
            case 'js':
                return \sprintf('<script src="%s"></script>', $this->asset['path']);
        }

        throw new Exception(\sprintf('%s() error: available with CSS, JS and images files only.', __FUNCTION__));
    }

    /**
     * Returns file's content.
     *
     * @return string
     */
    public function getInline(): string
    {
        if (!array_key_exists('content', $this->asset)) {
            throw new Exception(\sprintf('%s() error: available with CSS et JS files only.', __FUNCTION__));
        }

        return $this->asset['content'];
    }
}

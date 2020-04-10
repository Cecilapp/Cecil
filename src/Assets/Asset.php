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

use Cecil\Exception\Exception;
use Cecil\Util;

class Asset extends AbstractAsset
{
    protected $asset = [];
    protected $fileLoaded = false;

    /**
     * Loads a file.
     *
     * @param string     $path
     * @param array|null $options
     *
     * @return self
     */
    public function getFile(string $path, array $options = null): self
    {
        if (false === $filePath = $this->isFile($path)) {
            throw new Exception(sprintf('Asset file "%s" doesn\'t exist.', $path));
        }

        $this->fileLoaded = true;

        $baseurl = (string) $this->config->get('baseurl');
        $base = '';

        // handles options
        $canonical = null;
        $attributs = null;
        extract(is_array($options) ? $options : []);

        // set baseurl
        if ((bool) $this->config->get('canonicalurl') || $canonical === true) {
            $base = rtrim($baseurl, '/');
        }
        if ($canonical === false) {
            $base = '';
        }

        // prepares options
        $fileInfo = new \SplFileInfo($filePath);
        $this->asset['path'] = $base.'/'.ltrim($path, '/');
        $this->asset['ext'] = $fileInfo->getExtension();
        $this->asset['type'] = explode('/', mime_content_type($fileInfo->getPathname()))[0];
        if ($this->asset['type'] == 'text') {
            $this->asset['content'] = file_get_contents($fileInfo->getPathname());
        }
        $this->asset['attributs'] = $attributs;

        return $this;
    }

    /**
     * Checks if a (static) file exists.
     *
     * @param string $path
     *
     * @return string
     */
    public function isFile(string $path): string
    {
        $filePath = Util::joinFile($this->config->getStaticPath(), $path);

        if (Util::getFS()->exists($filePath)) {
            return $filePath;
        }

        // checks each theme
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
        if (!$this->fileLoaded) {
            throw new Exception(\sprintf('%s() error: you must load a file first.', __FUNCTION__));
        }

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
            default:
                throw new Exception(\sprintf('%s() error: available with CSS et JS files only.', __FUNCTION__));
        }
    }

    /**
     * Returns file's content.
     *
     * @return string
     */
    public function getInline(): string
    {
        if (!$this->fileLoaded) {
            throw new Exception(\sprintf('%s() error: you must load a file first.', __FUNCTION__));
        }
        if (!array_key_exists('content', $this->asset)) {
            throw new Exception(\sprintf('%s() error: available with CSS et JS files only.', __FUNCTION__));
        }

        return $this->asset['content'];
    }
}

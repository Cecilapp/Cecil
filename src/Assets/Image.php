<?php
/*
 * This file is part of the Cecil/Cecil package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Assets;

use Cecil\Builder;
use Cecil\Config;
use Cecil\Exception\Exception;
use Cecil\Util;
use Intervention\Image\Exception\NotReadableException;
use Intervention\Image\ImageManagerStatic as ImageManager;

class Image
{
    /** @var Config */
    private $config;
    /** @var int */
    private $size;
    /** @var string */
    private $source;
    /** @var string */
    private $destination = null;
    /** @var string */
    private $imageRelPath = null;
    /** @var string */
    private $thumbsDir = null;

    public function __construct(Builder $builder)
    {
        $this->config = $builder->getConfig();
    }

    /**
     * Resize an image.
     *
     * @param string $path Image path (relative from static/ dir or external)
     * @param int    $this->size Image new size (width)
     *
     * @return string Path to image thumbnail
     */
    public function resize(string $path, int $size): string
    {
        $external = false;
        $this->source = $path;
        $this->size = $size;

        // is external image?
        if (Util::isExternalUrl($path)) {
            $external = true;
        }

        if (!$external) {
            // source
            $this->source = $this->config->getStaticPath().'/'.$path;
            if (!Util::getFS()->exists($this->source)) {
                throw new Exception(sprintf('Can\'t resize "%s": file doesn\'t exits.', $path));
            }
            // destination
            // ie: .cache/images/thumbs
            $this->thumbsDir = (string) $this->config->get('cache.dir')
                .'/'.(string) $this->config->get('cache.images.dir')
                .'/'.(string) $this->config->get('cache.images.thumbs.dir')
                .'/'.$this->size;
            // ie: .cache/images/thumbs/img/logo.png
            $this->imageRelPath = $this->thumbsDir.'/'.ltrim($path, '/');
            // full absolute path
            $this->destination = $this->config->getDestinationDir().'/'.$this->imageRelPath;
            if ((bool) $this->config->get('cache.external')) {
                $this->destination = $this->imageRelPath;
            }
        }

        // is size is already OK?
        list($width, $height) = getimagesize($external ? $path : $this->source);
        if ($width <= $this->size && $height <= $this->size) {
            return $path;
        }

        // if GD extension is not installed: can't process
        if (!extension_loaded('gd')) {
            return $path;
        }

        // external image: return data URL
        if ($external) {
            try {
                $img = ImageManager::make($path);
            } catch (NotReadableException $e) {
                throw new Exception(sprintf('Cannot get image "%s"', $path));
            }

            return (string) $img->encode('data-url');
        }

        // resize
        $this->doResize();

        // return relative path
        return '/'.$this->config->get('cache.images.dir')
            .'/'.(string) $this->config->get('cache.images.thumbs.dir').'/'.$this->size.'/'.ltrim($path, '/');
    }

    /**
     * Resize with Intervention ImageManager.
     */
    private function doResize() {
        if (!Util::getFS()->exists($this->destination)) {
            $img = ImageManager::make($this->source);
            $img->resize($this->size, null, function (\Intervention\Image\Constraint $constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            // is a sub dir is necessary?
            $imageSubDir = Util::getFS()->makePathRelative('/'.dirname($this->imageRelPath), '/'.$this->thumbsDir.'/');
            if (!empty($imageSubDir)) {
                $destDir = $this->config->getCacheImagesThumbsPath().'/'.$this->size.'/'.$imageSubDir;
                Util::getFS()->mkdir($destDir);
            }
            $img->save($this->destination);
        }
    }
}

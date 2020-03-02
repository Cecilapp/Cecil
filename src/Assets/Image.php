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
    /** @var string */
    private $path;
    /** @var int */
    private $size;
    /** @var bool */
    private $external;
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
     * @param string $path       Image path (relative from static/ dir or external)
     * @param int    $this->size Image new size (width)
     *
     * @return string Path to image thumbnail
     */
    public function resize(string $path, int $size): string
    {
        $this->path = $path;
        $this->size = $size;
        $this->source = $path;

        // is external image?
        if (Util::isExternalUrl($this->path)) {
            $this->external = true;
        }

        if (!$this->external) {
            $this->source = $this->config->getStaticPath().'/'.ltrim($this->path, '/');
            if (!Util::getFS()->exists($this->source)) {
                throw new Exception(sprintf('Can\'t resize "%s": file doesn\'t exists.', $this->path));
            }
            // ie: .cache/images/thumbs/300
            $this->thumbsDir = (string) $this->config->get('cache.dir')
                .'/'.(string) $this->config->get('cache.images.dir')
                .'/'.(string) $this->config->get('cache.images.thumbs.dir')
                .'/'.$this->size;
            // ie: .cache/images/thumbs/300/img/logo.png
            $this->imageRelPath = $this->thumbsDir.'/'.ltrim($this->path, '/');
            // where to write file
            $this->destination = $this->config->getDestinationDir().'/'.$this->imageRelPath;
            if ($this->config->isCacheDirIsAbsolute()) {
                $this->destination = $this->imageRelPath;
            }
        }

        // is size is already OK?
        list($width, $height) = getimagesize($this->external ? $this->path : $this->source);
        if ($width <= $this->size && $height <= $this->size) {
            return $this->path;
        }

        // if GD extension is not installed: can't process
        if (!extension_loaded('gd')) {
            return $this->path;
        }

        return $this->doResize();
    }

    /**
     * Resize with Intervention ImageManager.
     *
     * @return string
     */
    private function doResize(): string
    {
        try {
            // external image
            if ($this->external) {
                try {
                    $img = ImageManager::make($this->path);
                    $img->resize($this->size, null, function (\Intervention\Image\Constraint $constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });
                } catch (NotReadableException $e) {
                    throw new Exception(sprintf('Cannot get image "%s"', $this->path));
                }

                // return data:image
                return (string) $img->encode('data-url');
            }
            // local image
            if (!Util::getFS()->exists($this->destination)) {
                $img = ImageManager::make($this->source);
                $img->resize($this->size, null, function (\Intervention\Image\Constraint $constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
                // is a sub dir is necessary?
                $imageSubDir = Util::getFS()->makePathRelative(
                    '/'.dirname($this->imageRelPath),
                    '/'.$this->thumbsDir.'/'
                );
                if (!empty($imageSubDir)) {
                    $destDir = $this->config->getCacheImagesThumbsPath().'/'.$this->size.'/'.$imageSubDir;
                    Util::getFS()->mkdir($destDir);
                }
                $img->save($this->destination);
            }

            // return relative path
            return '/'.$this->config->get('cache.images.dir')
                .'/'.(string) $this->config->get('cache.images.thumbs.dir')
                .'/'.$this->size
                .'/'.ltrim($this->path, '/');
        } catch (\Exception $e) {
            throw new \Exception(sprintf("Error during \"%s\" process.\n%s", get_class($this), $e->getMessage()));
        }
    }
}

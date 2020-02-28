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

    public function __construct(Builder $builder)
    {
        $this->config = $builder->getConfig();
    }

    /**
     * Resize an image.
     *
     * @param string $path Image path (relative from static/ dir or external)
     * @param int    $size Image new size (width)
     *
     * @return string Path to image thumbnail
     */
    public function resize(string $path, int $size): string
    {
        $external = false;

        // is external image?
        if (Util::isExternalUrl($path)) {
            $external = true;
            $source = $path;
        }

        if (!$external) {
            // source
            $source = $this->config->getStaticPath().'/'.$path;
            if (!Util::getFS()->exists($source)) {
                throw new Exception(sprintf('Can\'t resize "%s": file doesn\'t exits.', $path));
            }
            // destination
            // ie: .cache/images/thumbs
            $thumbsDir = (string) $this->config->get('cache.dir').'/'.$this->config->get('cache.images.dir').'/'.$this->config->get('cache.images.thumbs.dir').'/'.$size;
            // ie: .cache/images/thumbs/img/logo.png
            $imageRelPath = $thumbsDir.'/'.ltrim($path, '/');
            // full absolute path
            $destination = $this->config->getDestinationDir().'/'.$imageRelPath;
            if ((bool) $this->config->get('cache.external')) {
                $destination = $imageRelPath;
            }
        }

        // is size is already OK?
        list($width, $height) = getimagesize($external ? $path : $source);
        if ($width <= $size && $height <= $size) {
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
        if (!Util::getFS()->exists($destination)) {
            $img = ImageManager::make($source);
            $img->resize($size, null, function (\Intervention\Image\Constraint $constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            // is a sub dir is necessary?
            $imageSubDir = Util::getFS()->makePathRelative('/'.dirname($imageRelPath), '/'.$thumbsDir.'/');
            if (!empty($imageSubDir)) {
                $destDir = $this->config->getCacheImagesThumbsPath().'/'.$size.'/'.$imageSubDir;
                Util::getFS()->mkdir($destDir);
            }
            $img->save($destination);
        }

        // return relative path
        return '/'.$this->config->get('cache.images.dir').'/'.$this->config->get('cache.images.thumbs.dir').'/'.$size.'/'.ltrim($path, '/');
    }
}

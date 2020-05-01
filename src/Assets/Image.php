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
use Intervention\Image\Exception\NotReadableException;
use Intervention\Image\ImageManagerStatic as ImageManager;

class Image
{
    /** @var Builder */
    private $builder;
    /** @var Config */
    private $config;
    /** @var string */
    private $path;
    /** @var int */
    private $size;
    /** @var bool */
    private $local = true;

    const PREFIX = 'images/thumbs';

    public function __construct(Builder $builder)
    {
        $this->builder = $builder;
        $this->config = $builder->getConfig();
    }

    /**
     * Resizes an image.
     *
     * @param string $path Image path (relative from static/ dir or external).
     * @param int    $size Image new size (width).
     *
     * @return string Path to the image thumbnail
     */
    public function resize(string $path, int $size): string
    {
        // is not a local image?
        if (Util::isExternalUrl($path)) {
            $this->local = false;
        }

        $this->path = '/'.ltrim($path, '/');
        if (!$this->local) {
            $this->path = $path;
        }
        $this->size = $size;
        $returnPath = '/'.Util::joinPath(self::PREFIX, $this->size, $this->path);

        // source file
        $source = $this->getSource();

        // is size is already OK?
        list($width, $height) = getimagesize($source);
        if ($width <= $this->size && $height <= $this->size) {
            return $this->path;
        }

        // if GD extension is not installed: can't process
        if (!extension_loaded('gd')) {
            throw new Exception('GD extension is required to use images resize.');
        }

        $cache = new Cache($this->builder, 'assets', $this->config->getStaticPath());
        $cacheKey = ltrim($this->path, '/');
        if (!$cache->has($cacheKey)) {
            // image object
            try {
                $img = ImageManager::make($source);
                $img->resize($this->size, null, function (\Intervention\Image\Constraint $constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            } catch (NotReadableException $e) {
                throw new Exception(sprintf('Cannot get image "%s"', $this->path));
            }
            $cache->set($cacheKey, $img->encode());
        }
        $image = $cache->get($cacheKey, file_get_contents($source));

        // return data:image for external image
        if (!$this->local) {
            $mime = get_headers($source, 1)['Content-Type'];
            return sprintf('data:%s;base64,%s', $mime, base64_encode($image));
        }

        // save file
        $targetPathname = Util::joinFile($this->config->getOutputPath(), self::PREFIX, $this->size, $this->path);
        Util::getFS()->mkdir(dirname($targetPathname));
        Util::getFS()->dumpFile($targetPathname, $image);

        // return new path
        return $returnPath;
    }

    /**
     * Returns source path.
     */
    private function getSource()
    {
        if ($this->local) {
            $source = Util::joinFile($this->config->getStaticPath(), $this->path);
            if (!Util::getFS()->exists($source)) {
                throw new Exception(sprintf('Can\'t process "%s": file doesn\'t exists.', $source));
            }

            return $source;
        }
        $source = $this->path;
        if (!Util::isUrlFileExists($source)) {
            throw new Exception(sprintf('Can\'t process "%s": remonte file doesn\'t exists.', $source));
        }

        return $source;
    }
}

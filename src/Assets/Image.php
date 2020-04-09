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
use Cecil\Assets\AbstractAsset;
use Cecil\Util;
use Intervention\Image\Exception\NotReadableException;
use Intervention\Image\ImageManagerStatic as ImageManager;

class Image extends AbstractAsset
{
    /** @var string */
    private $path;
    /** @var int */
    private $size;
    /** @var bool */
    private $local = true;
    /** @var string */
    private $source;
    /** @var string */
    private $cachePath;
    /** @var string */
    private $destination = null;

    const CACHE_THUMBS_PATH = 'images/thumbs';

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
        $returnPath = '/'.Util::joinPath(self::CACHE_THUMBS_PATH, $this->size.$this->path);

        // source file
        $this->setSource();

        // images cache path
        $this->cachePath = Util::joinFile(
            $this->config->getCachePath(),
            self::CACHE_ASSETS_DIR,
            self::CACHE_THUMBS_PATH
        );

        // is size is already OK?
        list($width, $height) = getimagesize($this->source);
        if ($width <= $this->size && $height <= $this->size) {
            return $this->path;
        }

        // if GD extension is not installed: can't process
        if (!extension_loaded('gd')) {
            throw new Exception('GD extension is required to use images resize.');
        }

        $this->destination = Util::joinFile($this->cachePath, $this->size.$this->path);

        if (Util::getFS()->exists($this->destination)) {
            return $returnPath;
        }

        // image object
        try {
            $img = ImageManager::make($this->source);
            $img->resize($this->size, null, function (\Intervention\Image\Constraint $constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
        } catch (NotReadableException $e) {
            throw new Exception(sprintf('Cannot get image "%s"', $this->path));
        }

        // return data:image for external image
        if (!$this->local) {
            return (string) $img->encode('data-url');
        }

        // save file
        Util::getFS()->mkdir(dirname($this->destination));
        $img->save($this->destination);

        // return new path
        return $returnPath;
    }

    /**
     * Set the source file path.
     *
     * @return void
     */
    private function setSource(): void
    {
        if ($this->local) {
            $this->source = $this->config->getStaticPath().$this->path;
            if (!Util::getFS()->exists($this->source)) {
                throw new Exception(sprintf('Can\'t process "%s": file doesn\'t exists.', $this->source));
            }

            return;
        }

        $this->source = $this->path;
        if (!Util::isUrlFileExists($this->source)) {
            throw new Exception(sprintf('Can\'t process "%s": remonte file doesn\'t exists.', $this->source));
        }
    }
}

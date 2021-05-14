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
    protected $builder;
    /** @var Config */
    protected $config;
    /** @var string */
    private $path;
    /** @var int */
    private $size;
    /** @var bool */
    private $local = true;

    const PREFIX = 'images/thumbs';

    /**
     * @param Builder $builder
     */
    public function __construct(Builder $builder)
    {
        $this->builder = $builder;
        $this->config = $builder->getConfig();
    }

    /**
     * Loads an image from its file path.
     *
     * @param string $path Image path (relative from static/ dir or external).
     *
     * @return self
     */
    public function load(string $path): self
    {
        // is not a local image?
        if (Util\Url::isUrl($path)) {
            $this->local = false;
        }

        $this->path = '/'.ltrim($path, '/');
        if (!$this->local) {
            $this->path = $path;
        }

        return $this;
    }

    /**
     * Resizes an image.
     *
     * @param int $size Image new size (width).
     *
     * @return string Path to the image thumbnail
     */
    public function resize(int $size): string
    {
        if ($this->path === null) {
            throw new Exception('Image must be loaded before resize.');
        }

        $this->size = $size;

        // source file path
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

        $cache = new Cache($this->builder, 'assets');
        $returnPath = '/'.Util::joinPath(self::PREFIX, $this->size, $this->path);
        $cacheKey = $cache->createKeyFromFile($source, $returnPath);
        if (!$cache->has($cacheKey)) {
            // creates an image object
            try {
                $img = ImageManager::make($source);
                $img->resize($this->size, null, function (\Intervention\Image\Constraint $constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            } catch (NotReadableException $e) {
                throw new Exception(sprintf('Cannot get image "%s"', $this->path));
            }
            $cache->set($cacheKey, (string) $img->encode());
        }
        $image = $cache->get($cacheKey, Util\File::fileGetContents($source));

        // returns 'data:image' for external image
        if (!$this->local) {
            $mime = 'image';
            $context = stream_context_create([
                'ssl' => [
                    'verify_peer'      => false,
                    'verify_peer_name' => false,
                ],
            ]);
            $headers = get_headers($source, 1, is_resource($context) ? $context : null);
            if (array_key_exists('Content-Type', $headers)) {
                $mime = $headers['Content-Type'];
            }

            return sprintf('data:%s;base64,%s', $mime, base64_encode($image));
        }

        // save file
        if (!$this->builder->getBuildOptions()['dry-run']) {
            $targetPathname = Util::joinFile($this->config->getOutputPath(), self::PREFIX, $this->size, $this->path);
            Util\File::getFS()->mkdir(dirname($targetPathname));
            Util\File::getFS()->dumpFile($targetPathname, $image);
        }

        return $returnPath;
    }

    /**
     * Returns source path.
     */
    private function getSource()
    {
        if ($this->local) {
            $source = Util::joinFile($this->config->getStaticPath(), $this->path);
            if (!Util\File::getFS()->exists($source)) {
                throw new Exception(sprintf('Can\'t process "%s": file doesn\'t exists.', $source));
            }

            return $source;
        }
        $source = $this->path;
        if (!Util\Url::isRemoteFileExists($source)) {
            throw new Exception(sprintf('Can\'t process "%s": remote file doesn\'t exists.', $source));
        }

        return $source;
    }
}

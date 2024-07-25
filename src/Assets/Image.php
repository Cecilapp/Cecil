<?php

declare(strict_types=1);

/*
 * This file is part of Cecil.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Assets;

use Cecil\Exception\RuntimeException;
use Intervention\Image\Encoders\AutoEncoder;
use Intervention\Image\ImageManager;

class Image
{
    /**
     * Create new manager instance with desired driver.
     */
    private static function manager(): ImageManager
    {
        if (\extension_loaded('gd') && \function_exists('gd_info')) {
            return ImageManager::gd();
        }
        if (\extension_loaded('imagick') && class_exists('Imagick')) {
            return ImageManager::imagick();
        }

        throw new RuntimeException('PHP GD or Imagick extension is required.');
    }

    /**
     * Resize an image Asset.
     *
     * @throws RuntimeException
     */
    public static function resize(Asset $asset, int $width, int $quality): string
    {
        try {
            // is image Asset?
            if ($asset['type'] !== 'image') {
                throw new RuntimeException(sprintf('Not an image.'));
            }
            // creates image object from source
            $image = self::manager()->read($asset['content_source']);
            // resizes to $width with constraint the aspect-ratio and unwanted upsizing
            $image->scaleDown(width: $width);
            // return image data
            return (string) $image->encodeByMediaType($asset['subtype'], /** @scrutinizer ignore-type */ progressive: true, /** @scrutinizer ignore-type */ interlaced: false, quality: $quality);
        } catch (\Exception $e) {
            throw new RuntimeException(sprintf('Not able to resize "%s": %s', $asset['path'], $e->getMessage()));
        }
    }

    /**
     * Converts an image Asset to the target format.
     *
     * @throws RuntimeException
     */
    public static function convert(Asset $asset, string $format, int $quality): string
    {
        try {
            if ($asset['type'] !== 'image') {
                throw new RuntimeException(sprintf('Not an image.'));
            }
            $image = self::manager()->read($asset['content']);

            if (!\function_exists("image$format")) {
                throw new RuntimeException(sprintf('Function "image%s" is not available.', $format));
            }

            return (string) $image->encodeByExtension($format, /** @scrutinizer ignore-type */ progressive: true, /** @scrutinizer ignore-type */ interlaced: false, quality: $quality);
        } catch (\Exception $e) {
            throw new RuntimeException(sprintf('Not able to convert "%s": %s', $asset['path'], $e->getMessage()));
        }
    }

    /**
     * Returns the Data URL (encoded in Base64).
     *
     * @throws RuntimeException
     */
    public static function getDataUrl(Asset $asset, int $quality): string
    {
        try {
            if ($asset['type'] != 'image' || self::isSVG($asset)) {
                throw new RuntimeException(sprintf('Not an image.'));
            }
            $image = self::manager()->read($asset['content']);

            return (string) $image->encode(new AutoEncoder(quality: $quality))->toDataUri();
        } catch (\Exception $e) {
            throw new RuntimeException(sprintf('Can\'t get Data URL of "%s": %s', $asset['path'], $e->getMessage()));
        }
    }

    /**
     * Returns the dominant RGB color of an image asset.
     *
     * @throws RuntimeException
     */
    public static function getDominantColor(Asset $asset): string
    {
        try {
            if ($asset['type'] != 'image' || self::isSVG($asset)) {
                throw new RuntimeException(sprintf('Not an image.'));
            }
            $assetColor = clone $asset;
            $assetColor = $assetColor->resize(100);
            $image = self::manager()->read($assetColor['content']);

            return $image->pickColor(0, 0)->toString();
        } catch (\Exception $e) {
            throw new RuntimeException(sprintf('Can\'t get dominant color of "%s": %s', $asset['path'], $e->getMessage()));
        }
    }

    /**
     * Returns a Low Quality Image Placeholder (LQIP) as data URL.
     *
     * @throws RuntimeException
     */
    public static function getLqip(Asset $asset): string
    {
        try {
            if ($asset['type'] !== 'image') {
                throw new RuntimeException(sprintf('Not an image.'));
            }
            $assetLqip = clone $asset;
            $assetLqip = $assetLqip->resize(100);
            $image = self::manager()->read($assetLqip['content']);

            return (string) $image->blur(50)->encode()->toDataUri();
        } catch (\Exception $e) {
            throw new RuntimeException(sprintf('can\'t create LQIP of "%s": %s', $asset['path'], $e->getMessage()));
        }
    }

    /**
     * Build the `srcset` attribute for responsive images.
     * e.g.: `srcset="/img-480.jpg 480w, /img-800.jpg 800w"`.
     *
     * @throws RuntimeException
     */
    public static function buildSrcset(Asset $asset, array $widths): string
    {
        if ($asset['type'] !== 'image') {
            throw new RuntimeException(sprintf('can\'t build "srcset" of "%s": it\'s not an image file.', $asset['path']));
        }

        $srcset = '';
        $widthMax = 0;
        foreach ($widths as $width) {
            if ($asset['width'] < $width) {
                break;
            }
            $img = $asset->resize($width);
            $srcset .= sprintf('%s %sw, ', (string) $img, $width);
            $widthMax = $width;
        }
        // adds source image
        if (!empty($srcset) && ($asset['width'] < max($widths) && $asset['width'] != $widthMax)) {
            $srcset .= sprintf('%s %sw', (string) $asset, $asset['width']);
        }

        return rtrim($srcset, ', ');
    }

    /**
     * Returns the value of the "sizes" attribute corresponding to the configured class.
     */
    public static function getSizes(string $class, array $sizes = []): string
    {
        $result = '';
        $classArray = explode(' ', $class);
        foreach ($classArray as $class) {
            if (\array_key_exists($class, $sizes)) {
                $result = $sizes[$class] . ', ';
            }
        }
        if (!empty($result)) {
            return trim($result, ', ');
        }

        return $sizes['default'] ?? '100vw';
    }

    /**
     * Checks if an asset is an animated GIF.
     */
    public static function isAnimatedGif(Asset $asset): bool
    {
        // an animated GIF contains multiple "frames", with each frame having a header made up of:
        // 1. a static 4-byte sequence (\x00\x21\xF9\x04)
        // 2. 4 variable bytes
        // 3. a static 2-byte sequence (\x00\x2C)
        $count = preg_match_all('#\x00\x21\xF9\x04.{4}\x00[\x2C\x21]#s', (string) $asset['content_source']);

        return $count > 1;
    }

    /**
     * Returns true if asset is a SVG.
     */
    public static function isSVG(Asset $asset): bool
    {
        return \in_array($asset['subtype'], ['image/svg', 'image/svg+xml']) || $asset['ext'] == 'svg';
    }

    /**
     * Returns SVG attributes.
     *
     * @return \SimpleXMLElement|false
     */
    public static function getSvgAttributes(Asset $asset)
    {
        if (!self::isSVG($asset)) {
            return false;
        }

        if (false === $xml = simplexml_load_string($asset['content_source'] ?? '')) {
            return false;
        }

        return $xml->attributes();
    }
}

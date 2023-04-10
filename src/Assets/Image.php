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
use Intervention\Image\ImageManagerStatic as ImageManager;

class Image
{
    /**
     * Build the `srcset` attribute for responsive images.
     * e.g.: `srcset="/img-480.jpg 480w, /img-800.jpg 800w"`.
     *
     * @throws RuntimeException
     */
    public static function buildSrcset(Asset $asset, array $widths): string
    {
        if ($asset['type'] !== 'image') {
            throw new RuntimeException(\sprintf('can\'t build "srcset" of "%s": it\'s not an image file.', $asset['path']));
        }

        $srcset = '';
        $widthMax = 0;
        foreach ($widths as $width) {
            if ($asset['width'] < $width) {
                break;
            }
            $img = $asset->resize($width);
            $srcset .= \sprintf('%s %sw, ', (string) $img, $width);
            $widthMax = $width;
        }
        rtrim($srcset, ', ');
        // adds original image
        if (!empty($srcset) && ($asset['width'] != $widthMax)) {
            $srcset .= \sprintf('%s %sw', (string) $asset, $asset['width']);
        }

        return $srcset;
    }

    /**
     * Returns the value of the "sizes" attribute corresponding to the configured class.
     */
    public static function getSizes(string $class, array $config = []): string
    {
        $result = '';
        $classArray = explode(' ', $class);
        foreach ($classArray as $class) {
            if (array_key_exists($class, $config)) {
                $result = $config[$class] . ', ';
            }
        }
        if (!empty($result)) {
            return trim($result, ', ');
        }

        return $config['default'] ?? '100vw';
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
     * Returns the dominant hexadecimal color of an image asset.
     *
     * @throws RuntimeException
     */
    public static function getDominantColor(Asset $asset): string
    {
        if ($asset['type'] !== 'image') {
            throw new RuntimeException(\sprintf('can\'t get dominant color of "%s": it\'s not an image file.', $asset['path']));
        }

        $assetColor = clone $asset;
        $assetColor = $assetColor->resize(100);
        $img = ImageManager::make($assetColor['content']);
        $color = $img->limitColors(1)->pickColor(0, 0, 'hex');
        $img->destroy();

        return $color;
    }

    /**
     * Returns a Low Quality Image Placeholder (LQIP) as data URL.
     *
     * @throws RuntimeException
     */
    public static function getLqip(Asset $asset): string
    {
        if ($asset['type'] !== 'image') {
            throw new RuntimeException(\sprintf('can\'t create LQIP of "%s": it\'s not an image file.', $asset['path']));
        }

        $assetLqip = clone $asset;
        $assetLqip = $assetLqip->resize(100);
        return (string) ImageManager::make($assetLqip['content'])->blur(50)->encode('data-url');
    }
}

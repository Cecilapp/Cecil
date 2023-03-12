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
     * e.g.: srcset="/img-480.jpg 480w, /img-800.jpg 800w".
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
            if ($asset->getWidth() < $width) {
                break;
            }
            $img = $asset->resize($width);
            $srcset .= \sprintf('%s %sw, ', (string) $img, $width);
            $widthMax = $width;
        }
        rtrim($srcset, ', ');
        // add reference image
        if (!empty($srcset) && ($asset->getWidth() != $widthMax)) {
            $srcset .= \sprintf('%s %sw', (string) $asset, $asset->getWidth());
        }

        return $srcset;
    }

    /**
     * Converts an image asset to WebP format.
     *
     * @throws RuntimeException
     */
    public static function convertTopWebp(Asset $asset, int $quality): Asset
    {
        if ($asset['type'] !== 'image') {
            throw new RuntimeException(\sprintf('can\'t convert "%s" (%s) to WebP: it\'s not an image file.', $asset['path'], $asset['type']));
        }

        $assetWebp = clone $asset;
        $format = 'webp';
        $image = ImageManager::make($assetWebp['content']);
        $assetWebp['content'] = (string) $image->encode($format, $quality);
        $assetWebp['path'] = preg_replace('/\.' . $asset['ext'] . '$/m', ".$format", $asset['path']);
        $assetWebp['ext'] = $format;

        return $assetWebp;
    }

    /**
     * Checks if an asset is an animated gif.
     */
    public static function isAnimatedGif(Asset $asset): bool
    {
        // an animated gif contains multiple "frames", with each frame having a header made up of:
        // * a static 4-byte sequence (\x00\x21\xF9\x04)
        // * 4 variable bytes
        // * a static 2-byte sequence (\x00\x2C)
        $count = preg_match_all('#\x00\x21\xF9\x04.{4}\x00[\x2C\x21]#s', (string) $asset['content_source']);

        return $count > 1;
    }

    /**
     * Returns the dominant hex color of an image asset.
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
        $image = ImageManager::make($assetColor['content']);
        $color = $image->limitColors(1)->pickColor(0, 0, 'hex');
        $image->destroy();

        return $color;
    }

    /**
     * Returns the value of "sizes" corresponding to the configured class.
     */
    public static function getSizes(string $class, array $config): string
    {
        $classArray = explode(' ', $class);
        foreach ($classArray as $class) {
            if (array_key_exists($class, $config)) {
                $result = $config[$class] . ', ';
            }
        }
        if (!empty($result)) {
            return trim($result, ', ');
        }

        return $config['default'];
    }
}

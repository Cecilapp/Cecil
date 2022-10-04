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
use Spatie\ImageOptimizer\OptimizerChain;
use Spatie\ImageOptimizer\Optimizers\Cwebp;
use Spatie\ImageOptimizer\Optimizers\Gifsicle;
use Spatie\ImageOptimizer\Optimizers\Jpegoptim;
use Spatie\ImageOptimizer\Optimizers\Optipng;
use Spatie\ImageOptimizer\Optimizers\Pngquant;
use Spatie\ImageOptimizer\Optimizers\Svgo;

class Image
{
    /**
     * Image Optimizer Chain.
     */
    public static function optimizer(int $quality): OptimizerChain
    {
        return (new OptimizerChain())
            ->addOptimizer(new Jpegoptim([
                "--max=$quality",
                '--strip-all',
                '--all-progressive',
            ]))
            ->addOptimizer(new Pngquant([
                "--quality=$quality",
                '--force',
                '--skip-if-larger',
            ]))
            ->addOptimizer(new Optipng([
                '-i0',
                '-o2',
                '-quiet',
            ]))
            ->addOptimizer(new Svgo([
                '--disable={cleanupIDs,removeViewBox}',
            ]))
            ->addOptimizer(new Gifsicle([
                '-b',
                '-O3',
            ]))
            ->addOptimizer(new Cwebp([
                '-m 6',
                '-pass 10',
                '-mt',
                '-q $quality',
            ]));
    }

    /**
     * Build the `srcset` attribute for responsive images.
     * ie: srcset="/img-480.jpg 480w, /img-800.jpg 800w".
     *
     * @throws RuntimeException
     */
    public static function buildSrcset(Asset $asset, array $widths): string
    {
        if ($asset['type'] !== 'image') {
            throw new RuntimeException(\sprintf('Can not build "srcset" of "%s": it\'s not an image file.', $asset['path']));
        }

        $srcset = '';
        foreach ($widths as $width) {
            if ($asset->getWidth() < $width) {
                break;
            }
            $img = $asset->resize($width);
            $srcset .= \sprintf('%s %sw, ', $img, $width);
        }
        rtrim($srcset, ', ');
        // add reference image
        if (!empty($srcset)) {
            $srcset .= \sprintf('%s %sw', (string) $asset, $asset->getWidth());
        }

        return $srcset;
    }

    /**
     * Converts an asset to WebP format.
     *
     * @throws RuntimeException
     */
    public static function convertTopWebp(Asset $asset, int $quality): Asset
    {
        if ($asset['type'] !== 'image') {
            throw new RuntimeException(\sprintf('Can not convert "%s" to WebP: it\'s not an image file.', $asset['path']));
        }

        $assetWebp = clone $asset;
        $format = 'webp';
        $image = ImageManager::make($assetWebp['content']);
        $assetWebp['content'] = (string) $image->encode($format, $quality);
        $assetWebp['path'] = preg_replace('/\.'.$asset['ext'].'$/m', ".$format", $asset['path']);
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
            throw new RuntimeException(\sprintf('Can not get dominant color of "%s": it\'s not an image file.', $asset['path']));
        }

        $assetColor = clone $asset;
        $assetColor = $assetColor->resize(100);
        $image = ImageManager::make($assetColor['content']);
        $color = $image->limitColors(1)->pickColor(0, 0, 'hex');
        $image->destroy();

        return $color;
    }
}

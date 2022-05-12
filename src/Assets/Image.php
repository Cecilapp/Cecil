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

use Cecil\Assets\Image\Optimizers\Cwebp;
use Intervention\Image\ImageManagerStatic as ImageManager;
use Spatie\ImageOptimizer\OptimizerChain;
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
     */
    public static function buildSrcset(Asset $asset, array $widths): string
    {
        dump($asset->data['path']);

        $srcset = '';
        foreach ($widths as $width) {
            if ($asset->getWidth() < $width) {
                break;
            }
            $img = $asset->resize($width);
            $srcset .= sprintf('%s %sw, ', $img, $width);
        }
        rtrim($srcset, ', ');
        // add reference image
        if (!empty($srcset)) {
            $srcset .= sprintf('%s %sw', (string) $asset, $asset->getWidth());
        }

        dump($img->data['path']);
        dump($asset->data['path']);
        die();

        return $srcset;
    }

    /**
     * Converts an asset image to WebP.
     */
    public static function convertTopWebp(Asset $asset, int $quality): Asset
    {
        $assetWebp = clone $asset;
        $format = 'webp';
        $image = ImageManager::make($assetWebp['content']);
        $assetWebp['content'] = (string) $image->encode($format, $quality);
        $assetWebp['path'] = preg_replace('/\.'.$asset['ext'].'$/m', ".$format", $asset['path']);
        $assetWebp['ext'] = $format;

        return $assetWebp;
    }

    /**
     * Tests if an asset is an animated gif.
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
}

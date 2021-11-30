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
            ]));
    }

    /**
     * Build the `srcset` attribute for responsive images.
     * ie: srcset="/img-480.jpg 480w, /img-800.jpg 800w".
     */
    public static function getSrcset(Asset $asset, int $steps, int $wMin, int $wMax): string
    {
        $srcset = '';
        for ($i = 1; $i <= $steps; $i++) {
            $w = ceil($wMin * $i);
            if ($w > $asset->getWidth() || $w > $wMax) {
                break;
            }
            $a = clone $asset;
            $img = $a->resize(intval($w));
            $srcset .= sprintf('%s %sw', $img, $w);
            if ($i < $steps) {
                $srcset .= ', ';
            }
        }
        // add reference image
        if (!empty($srcset)) {
            $srcset .= sprintf('%s %sw', (string) $asset, $asset->getWidth());
        }

        return $srcset;
    }

    /**
     * Converts an asset image to WebP.
     */
    public static function convertTopWebp(Asset $asset, int $quality): Asset
    {
        $assetWebp = clone $asset;
        $format = 'webp';
        $image = ImageManager::make($assetWebp['content_source']);
        $assetWebp['content'] = (string) $image->encode($format, $quality);
        $assetWebp['path'] = preg_replace('/\.'.$asset['ext'].'$/m', ".$format", $asset['path']);
        $assetWebp['ext'] = $format;

        return $assetWebp;
    }
}

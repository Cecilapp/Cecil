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

use Spatie\ImageOptimizer\OptimizerChain;
use Spatie\ImageOptimizer\Optimizers\Jpegoptim;
use Spatie\ImageOptimizer\Optimizers\Pngquant;
use Spatie\ImageOptimizer\Optimizers\Optipng;
use Spatie\ImageOptimizer\Optimizers\Svgo;
use Spatie\ImageOptimizer\Optimizers\Gifsicle;

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
}

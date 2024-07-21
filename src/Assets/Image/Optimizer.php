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

namespace Cecil\Assets\Image;

use Spatie\ImageOptimizer\OptimizerChain;
use Spatie\ImageOptimizer\Optimizers\Avifenc;
use Spatie\ImageOptimizer\Optimizers\Cwebp;
use Spatie\ImageOptimizer\Optimizers\Gifsicle;
use Spatie\ImageOptimizer\Optimizers\Jpegoptim;
use Spatie\ImageOptimizer\Optimizers\Optipng;
use Spatie\ImageOptimizer\Optimizers\Pngquant;
use Spatie\ImageOptimizer\Optimizers\Svgo;

class Optimizer
{
    /**
     * Image Optimizer Chain.
     */
    public static function create(int $quality): OptimizerChain
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
            ]))
            ->addOptimizer(new Avifenc([
                '-a cq-level=' . round(63 - $quality * 0.63),
                '-j all',
                '--min 0',
                '--max 63',
                '--minalpha 0',
                '--maxalpha 63',
                '-a end-usage=q',
                '-a tune=ssim',
            ]));
    }
}

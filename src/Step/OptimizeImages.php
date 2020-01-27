<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Step;

use Spatie\ImageOptimizer\OptimizerChainFactory;

/**
 * Images Optimization.
 */
class OptimizeImages extends AbstractStepOptimize
{
    public function setProcessor()
    {
        $this->type = 'images';
        $this->processor = OptimizerChainFactory::create();
    }

    public function processFile(\Symfony\Component\Finder\SplFileInfo $file)
    {
        $this->processor->optimize($file->getPathname());
    }
}

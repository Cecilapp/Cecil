<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Step;

use Cecil\Util;
use Spatie\ImageOptimizer\OptimizerChainFactory as Optimizer;

/**
 * Post process image files.
 */
class PostProcessImages extends AbstractPostProcess
{
    public function setProcessor()
    {
        $this->type = 'images';
        $this->processor = Optimizer::create();
    }

    public function processFile()
    {
        $cachedFile = Util::joinFile([$this->config->getCachePath(), $this->inputFile->getRelativePathname()]);

        if (!Util::getFS()->exists($cachedFile)) {
            Util::getFS()->mkdir(Util::joinFile([$this->config->getCachePath(), $this->inputFile->getRelativePath()]));
            /** @var Optimizer $processor */
            $this->processor->optimize($this->inputFile->getPathname(), $cachedFile);
        }

        $this->outputFile = new \SplFileInfo($cachedFile);
    }
}

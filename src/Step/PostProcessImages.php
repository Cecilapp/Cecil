<?php
/**
 * This file is part of the Cecil/Cecil package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Step;

use Spatie\ImageOptimizer\OptimizerChainFactory as Optimizer;

/**
 * Post process image files.
 */
class PostProcessImages extends AbstractPostProcess
{
    /**
     * {@inheritdoc}
     */
    public function init($options)
    {
        $this->type = 'images';
        parent::init($options);
    }

    /**
     * {@inheritdoc}
     */
    public function setProcessor()
    {
        $this->processor = Optimizer::create();
    }

    /**
     * {@inheritdoc}
     */
    public function processFile(\Symfony\Component\Finder\SplFileInfo $file)
    {
        /** @var Optimizer $processor */
        $this->processor->optimize($file->getPathname());
    }
}

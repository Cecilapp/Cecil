<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Step;

use Spatie\ImageOptimizer\OptimizerChainFactory;
use Symfony\Component\Finder\Finder;

/**
 * Images Optimization.
 */
class OptimizeImages extends AbstractStep
{
    /**
     * {@inheritdoc}
     */
    public function init($options)
    {
        if (is_dir($this->builder->getConfig()->getOutputPath())) {
            $this->process = true;
        }
        if (false === $this->builder->getConfig()->get('optimize.images.enabled')) {
            $this->process = false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function process()
    {
        call_user_func_array($this->builder->getMessageCb(), ['OPTIMIZE', 'Optimizing images']);

        $optimizerChain = OptimizerChainFactory::create();

        $data = Finder::create()
            ->files()
            ->in($this->builder->getConfig()->getOutputPath())
            ->name('/\.('.implode('|', $this->builder->getConfig()->get('optimize.images.ext')).')$/')
            ->sortByName(true);

        $count = 0;
        $max = count($data);

        if ($max <= 0) {
            $message = 'No images';
            call_user_func_array($this->builder->getMessageCb(), ['OPTIMIZE_PROGRESS', $message]);

            return;
        }

        /* @var $file \Symfony\Component\Finder\SplFileInfo */
        foreach ($data as $file) {
            $count++;

            $sizeBefore = $file->getSize();
            $optimizerChain->optimize($file->getPathname());
            $sizeAfter = $file->getSize();
            //$sizeAfter = filesize($file->getPathname());

            $message = sprintf(
                '"%s" processed (%s Ko -> %s Ko)',
                $file->getFilename(),
                $sizeBefore/1000,
                $sizeAfter/1000
            );
            call_user_func_array($this->builder->getMessageCb(), ['OPTIMIZE_PROGRESS', $message, $count, $max]);
        }
    }
}

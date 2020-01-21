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
class ImagesOptim extends AbstractStep
{
    /**
     * {@inheritdoc}
     */
    public function init($options)
    {
        if (!is_dir($this->builder->getConfig()->get('process.images.dir'))
        && false === $this->builder->getConfig()->get('process.images.enabled')) {
            $this->process = false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function process()
    {
        call_user_func_array($this->builder->getMessageCb(), ['PROCESS', 'Images optimization']);

        $optimizerChain = OptimizerChainFactory::create();

        $data = Finder::create()
            ->files()
            ->in($this->builder->getConfig()->getStaticPath())
            ->name('/\.('.implode('|', $this->builder->getConfig()->get('process.images.ext')).')$/')
            ->sortByName(true);

        $count = 0;
        $max = count($data);

        /* @var $file \Symfony\Component\Finder\SplFileInfo */
        foreach ($data as $file) {
            $count++;

            $optimizerChain->optimize($file->getPathname());

            $message = sprintf('"%s" processed', $file->getPathname());
            call_user_func_array($this->builder->getMessageCb(), ['PROCESS_PROGRESS', $message, $count, $max]);
        }
    }
}

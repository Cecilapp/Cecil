<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Step;

use MatthiasMullie\Minify;
use Symfony\Component\Finder\Finder;

/**
 * CSS files Optimization.
 */
class OptimizeCss extends AbstractStep
{
    const TYPE = 'css';


    /**
     * {@inheritdoc}
     */
    public function init($options)
    {
        if (false === $this->builder->getConfig()->get(sprintf('optimize.%s.enabled', self::TYPE))
            || false === $this->builder->getConfig()->get('optimize.enabled'))
        {
            $this->process = false;

            return;
        }
        if ($options['dry-run']) {
            $this->process = false;

            return;
        }
        if (is_dir($this->builder->getConfig()->getOutputPath())) {
            $this->process = true;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function process()
    {
        call_user_func_array($this->builder->getMessageCb(), ['OPTIMIZE', sprintf('Optimizing %s', self::TYPE)]);

        $extensions = $this->builder->getConfig()->get(sprintf('optimize.%s.ext', self::TYPE));
        $files = Finder::create()
            ->files()
            ->in($this->builder->getConfig()->getOutputPath())
            ->name('/\.('.implode('|', $extensions).')$/')
            ->notName('/\.min\.(' . implode('|', $extensions) . ')$/')
            ->sortByName(true);
        $max = count($files);

        if ($max <= 0) {
            $message = 'No files';
            call_user_func_array($this->builder->getMessageCb(), ['OPTIMIZE_PROGRESS', $message]);

            return;
        }

        $count = 0;
        $optimized = 0;

        // setup processor
	    //

        /* @var $file \Symfony\Component\Finder\SplFileInfo */
        foreach ($files as $file) {
            $sizeBefore = 0;
            $sizeAfter = 0;
            $count++;

            $sizeBefore = $file->getSize();

            // process file
            $minifier = new Minify\CSS($file->getPathname());
            $minified = $minifier->minify();
            \Cecil\Util::getFS()->dumpFile($file->getPathname(), $minified);

            $sizeAfter = $file->getSize();

            $subpath = \Cecil\Util::getFS()->makePathRelative(
                $file->getPath(),
                $this->builder->getConfig()->getOutputPath()
            );
            $subpath = trim($subpath, './');
            $path = $subpath ? $subpath.'/'.$file->getFilename() : $file->getFilename();

            $message = sprintf(
                '%s: %s Ko -> %s Ko',
                $path,
                ceil($sizeBefore / 1000),
                ceil($sizeAfter / 1000)
            );
            call_user_func_array($this->builder->getMessageCb(), ['OPTIMIZE_PROGRESS', $message, $count, $max]);
            if ($sizeAfter < $sizeBefore) {
                $optimized++;
            }
        }
        if ($optimized == 0) {
            $message = 'Nothing to do';
            call_user_func_array($this->builder->getMessageCb(), ['OPTIMIZE_PROGRESS', $message]);
        }
    }
}

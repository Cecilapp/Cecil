<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Step;

use Cecil\Exception\Exception;
use Symfony\Component\Finder\Finder;

/**
 * Static Files Processing.
 */
abstract class AbstractStaticProcess extends AbstractStep
{
    /**
     * File type (ie: 'css').
     */
    protected $type = 'type';
    /**
     * File processor.
     */
    protected $processor;

    /**
     * {@inheritdoc}
     */
    public function init($options)
    {
        if ($options['dry-run']) {
            $this->process = false;

            return;
        }
        if (false === $this->builder->getConfig()->get(sprintf('optimize.%s.enabled', $this->type))) {
            $this->process = false;

            return;
        }
        if (true === $this->builder->getConfig()->get('optimize.enabled')) {
            $this->process = true;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function process()
    {
        $this->setProcessor();

        call_user_func_array($this->builder->getMessageCb(), ['OPTIMIZE', sprintf('Optimizing %s', $this->type)]);

        $extensions = $this->builder->getConfig()->get(sprintf('optimize.%s.ext', $this->type));
        if (empty($extensions)) {
            throw new Exception(sprintf('The config key "optimize.%s.ext" is empty', $this->type));
        }

        $files = Finder::create()
            ->files()
            ->in($this->builder->getConfig()->getOutputPath())
            ->name('/\.('.implode('|', $extensions).')$/')
            ->notName('/\.min\.('.implode('|', $extensions).')$/')
            ->sortByName(true);
        $max = count($files);

        if ($max <= 0) {
            $message = 'No files';
            call_user_func_array($this->builder->getMessageCb(), ['OPTIMIZE_PROGRESS', $message]);

            return;
        }

        $count = 0;
        $optimized = 0;

        /* @var $file \Symfony\Component\Finder\SplFileInfo */
        foreach ($files as $file) {
            $count++;

            $sizeBefore = $file->getSize();

            $this->processFile($file);

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

    /**
     * Set file processor object.
     *
     * @return void
     */
    abstract public function setProcessor();

    /**
     * Process a file.
     *
     * @param \Symfony\Component\Finder\SplFileInfo $file
     *
     * @return void
     */
    abstract public function processFile(\Symfony\Component\Finder\SplFileInfo $file);
}

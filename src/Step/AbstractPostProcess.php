<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Step;

use Cecil\Exception\Exception;
use Cecil\Util;
use Symfony\Component\Finder\Finder;

/**
 * Post Processing.
 */
abstract class AbstractPostProcess extends AbstractStep
{
    /** @var string File type (ie: 'css') */
    protected $type = 'type';
    /** @var mixed File processor */
    protected $processor;

    const CACHE_FILES = 'postprocess/files';
    const CACHE_HASH = 'postprocess/hash';

    /**
     * {@inheritdoc}
     */
    public function init($options)
    {
        if ($options['dry-run']) {
            $this->process = false;

            return;
        }
        if (false === $this->builder->getConfig()->get(sprintf('postprocess.%s.enabled', $this->type))) {
            $this->process = false;

            return;
        }
        if (true === $this->builder->getConfig()->get('postprocess.enabled')) {
            $this->process = true;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function process()
    {
        $this->setProcessor();

        call_user_func_array(
            $this->builder->getMessageCb(),
            ['POSTPROCESS', sprintf('Post-processing %s', $this->type)]
        );

        $extensions = $this->builder->getConfig()->get(sprintf('postprocess.%s.ext', $this->type));
        if (empty($extensions)) {
            throw new Exception(sprintf('The config key "postprocess.%s.ext" is empty', $this->type));
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
            call_user_func_array($this->builder->getMessageCb(), ['POSTPROCESS_PROGRESS', $message]);

            return;
        }

        $count = 0;
        $postprocessed = 0;

        /** @var \Symfony\Component\Finder\SplFileInfo $file */
        foreach ($files as $file) {
            $count++;

            $sizeBefore = $file->getSize();

            $hash = hash_file('md5', $file->getPathname());
            $processedFile = Util::joinFile($this->config->getCachePath(), self::CACHE_FILES, $file->getRelativePathname());
            $hashFile = Util::joinFile($this->config->getCachePath(), self::CACHE_HASH, $hash);

            if (!Util::getFS()->exists($processedFile)
            || hash_file('md5', $file->getPathname()) != $hash) {
                $this->processFile($file);

                Util::getFS()->copy($file->getPathname(), $processedFile, true);
                Util::getFS()->mkdir(Util::joinFile($this->config->getCachePath(), self::CACHE_HASH));
                Util::getFS()->touch($hashFile);
            }

            $sizeAfter = $file->getSize();

            $message = sprintf(
                '%s: %s Ko -> %s Ko',
                $file->getRelativePathname(),
                ceil($sizeBefore / 1000),
                ceil($sizeAfter / 1000)
            );
            call_user_func_array($this->builder->getMessageCb(), ['POSTPROCESS_PROGRESS', $message, $count, $max]);
            if ($sizeAfter < $sizeBefore) {
                $postprocessed++;
            }
        }
        if ($postprocessed == 0) {
            $message = 'Nothing to do';
            call_user_func_array($this->builder->getMessageCb(), ['POSTPROCESS_PROGRESS', $message]);
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

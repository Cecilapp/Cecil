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

use Cecil\Assets\PostProcessCache;
use Cecil\Exception\Exception;
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
            $this->canProcess = false;

            return;
        }
        if (false === $this->builder->getConfig()->get(sprintf('postprocess.%s.enabled', $this->type))) {
            $this->canProcess = false;

            return;
        }
        if (true === $this->builder->getConfig()->get('postprocess.enabled')) {
            $this->canProcess = true;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function process()
    {
        $this->setProcessor();

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
            $this->builder->getLogger()->info('No files');

            return;
        }

        $count = 0;
        $postprocessed = 0;
        $cache = new PostProcessCache($this->builder, 'postprocess', $this->config->getOutputPath());

        /** @var \Symfony\Component\Finder\SplFileInfo $file */
        foreach ($files as $file) {
            $count++;
            $sizeBefore = $file->getSize();
            $message = $file->getRelativePathname();
            $content = file_get_contents($file->getPathname());

            if (!$cache->hasWithHash($file->getRelativePathname(), $cache->createHash($content))) {
                $this->processFile($file);
                $postprocessedContent = file_get_contents($file->getPathname());
                $sizeAfter = $file->getSize();
                if ($sizeAfter < $sizeBefore) {
                    $message = sprintf(
                        '%s (%s Ko -> %s Ko)',
                        $file->getRelativePathname(),
                        ceil($sizeBefore / 1000),
                        ceil($sizeAfter / 1000)
                    );
                }
                $postprocessed++;

                $cache->setWithHash(
                    $file->getRelativePathname(),
                    $postprocessedContent,
                    null,
                    $cache->createHash($content)
                );

                $this->builder->getLogger()->info($message, ['progress' => [$count, $max]]);
            }
        }
        if ($postprocessed == 0) {
            $this->builder->getLogger()->info('Nothing to do');
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

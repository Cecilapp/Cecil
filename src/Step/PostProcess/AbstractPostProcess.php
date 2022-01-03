<?php
/**
 * This file is part of the Cecil/Cecil package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Step\PostProcess;

use Cecil\Assets\Cache;
use Cecil\Exception\RuntimeException;
use Cecil\Step\AbstractStep;
use Cecil\Util;
use Symfony\Component\Finder\Finder;

/**
 * Post Processing.
 */
abstract class AbstractPostProcess extends AbstractStep
{
    /** @var string File type (ie: 'css') */
    protected $type;

    /** @var mixed File processor */
    protected $processor;

    /**
     * {@inheritdoc}
     */
    public function init($options)
    {
        if ($options['dry-run']) {
            $this->canProcess = false;

            return;
        }
        if (false === $this->builder->getConfig()->get(\sprintf('postprocess.%s.enabled', $this->type))) {
            $this->canProcess = false;

            return;
        }
        if (true === $this->builder->getConfig()->get('postprocess.enabled')) {
            $this->canProcess = true;
        }
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException
     */
    public function process()
    {
        $this->setProcessor();

        $extensions = $this->builder->getConfig()->get(\sprintf('postprocess.%s.ext', $this->type));
        if (empty($extensions)) {
            throw new RuntimeException(\sprintf('The config key "postprocess.%s.ext" is empty', $this->type));
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
        $cache = new Cache($this->builder, 'postprocess');

        /** @var \Symfony\Component\Finder\SplFileInfo $file */
        foreach ($files as $file) {
            $count++;
            $sizeBefore = $file->getSize();
            $message = sprintf('File "%s" post-processed', $file->getRelativePathname());

            $cacheKey = $cache->createKeyFromPath($file->getPathname(), $file->getRelativePathname());
            if (!$cache->has($cacheKey)) {
                $processed = $this->processFile($file);
                $sizeAfter = strlen($processed);
                if ($sizeAfter < $sizeBefore) {
                    $message = sprintf(
                        'File "%s" compressed (%s Ko -> %s Ko)',
                        $file->getRelativePathname(),
                        ceil($sizeBefore / 1000),
                        ceil($sizeAfter / 1000)
                    );
                }
                $cache->set($cacheKey, $this->encode($processed));
                $postprocessed++;

                $this->builder->getLogger()->info($message, ['progress' => [$count, $max]]);
            }
            $processed = $this->decode($cache->get($cacheKey));
            Util\File::getFS()->dumpFile($file->getPathname(), $processed);
        }
        if ($postprocessed == 0) {
            $this->builder->getLogger()->info('Nothing to do');
        }
    }

    /**
     * Set file processor object.
     */
    abstract public function setProcessor(): void;

    /**
     * Process a file.
     */
    abstract public function processFile(\Symfony\Component\Finder\SplFileInfo $file): string;

    /**
     * Encode file content.
     */
    public function encode(string $content = null): ?string
    {
        return $content;
    }

    /**
     * Decode file content.
     */
    public function decode(string $content = null): ?string
    {
        return $content;
    }
}

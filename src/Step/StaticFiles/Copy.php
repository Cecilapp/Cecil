<?php

declare(strict_types=1);

/*
 * This file is part of Cecil.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Step\StaticFiles;

use Cecil\Exception\RuntimeException;
use Cecil\Step\AbstractStep;
use Cecil\Util;
use Symfony\Component\Finder\Finder;

/**
 * Copying static files to site root.
 */
class Copy extends AbstractStep
{
    protected $count = 0;

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'Copying static';
    }

    /**
     * {@inheritdoc}
     */
    public function init(array $options): void
    {
        if ($options['dry-run']) {
            return;
        }

        // reset output directory only if it's not partial rendering
        if (empty($options['render-subset'])) {
            Util\File::getFS()->remove($this->config->getOutputPath());
            Util\File::getFS()->mkdir($this->config->getOutputPath());
        }

        $this->canProcess = true;
    }

    /**
     * {@inheritdoc}
     */
    public function process(): void
    {
        $target = (string) $this->config->get('static.target');
        $exclude = (array) $this->config->get('static.exclude');

        // copying assets in debug mode (for source maps)
        if ($this->builder->isDebug() && $this->config->isEnabled('assets.compile.sourcemap')) {
            // copying content of '<theme>/assets/' dir if exists
            if ($this->config->hasTheme()) {
                $themes = array_reverse($this->config->getTheme());
                foreach ($themes as $theme) {
                    $this->copy($this->config->getThemeDirPath($theme, 'assets'));
                }
            }
            // copying content of 'assets/' dir if exists
            $this->copy($this->config->getAssetsPath());
            // cancel exclusion for static files (see below)
            $exclude = [];
        }

        // copying content of '<theme>/static/' dir if exists
        if ($this->config->hasTheme()) {
            $themes = array_reverse($this->config->getTheme());
            foreach ($themes as $theme) {
                $this->copy($this->config->getThemeDirPath($theme, 'static'), $target, $exclude);
            }
        }

        // copying content of 'static/' dir if exists
        $this->copy($this->config->getStaticPath(), $target, $exclude);

        // copying mounts
        if ($this->config->get('static.mounts')) {
            foreach ((array) $this->config->get('static.mounts') as $source => $destination) {
                $this->copy(Util::joinFile($this->config->getStaticPath(), (string) $source), (string) $destination);
            }
        }

        if ($this->count === 0) {
            $this->builder->getLogger()->info('Nothing to copy');

            return;
        }
        $this->builder->getLogger()->info('Files copied', ['progress' => [$this->count, $this->count]]);
    }

    /**
     * Copying a file or files in a directory from $from (if exists) to $to (relative to output path).
     * Exclude files or directories with $exclude array.
     */
    protected function copy(string $from, ?string $to = null, ?array $exclude = null): void
    {
        try {
            if (Util\File::getFS()->exists($from)) {
                // copy a file
                if (is_file($from)) {
                    Util\File::getFS()->copy($from, Util::joinFile($this->config->getOutputPath(), $to), true);

                    return;
                }
                // copy a directory
                $finder = Finder::create()
                    ->files()
                    ->in($from)
                    ->ignoreDotFiles(false);
                // exclude files or directories
                if (\is_array($exclude)) {
                    $finder->notPath($exclude);
                    $finder->notName($exclude);
                }
                $this->count += $finder->count();
                Util\File::getFS()->mirror(
                    $from,
                    Util::joinFile($this->config->getOutputPath(), $to ?? ''),
                    $finder,
                    ['override' => true]
                );
            }
        } catch (\Exception $e) {
            throw new RuntimeException(\sprintf('Error during static files copy: %s', $e->getMessage()));
        }
    }
}

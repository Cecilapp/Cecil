<?php
/**
 * This file is part of the Cecil/Cecil package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Step\StaticFiles;

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
    public function init($options)
    {
        if ($options['dry-run']) {
            $this->canProcess = false;

            return;
        }

        // reset output directory
        Util\File::getFS()->remove($this->config->getOutputPath());
        Util\File::getFS()->mkdir($this->config->getOutputPath());

        $this->canProcess = true;
    }

    /**
     * {@inheritdoc}
     */
    public function process()
    {
        // copying content of '<theme>/static/' dir if exists
        if ($this->config->hasTheme()) {
            $themes = array_reverse($this->config->getTheme());
            foreach ($themes as $theme) {
                $this->copy(
                    $this->config->getThemeDirPath($theme, 'static'),
                    $this->config->get('static.target'),
                    $this->config->get('static.exclude')
                );
            }
        }

        // copying content of 'static/' dir if exists
        $this->copy(
            $this->config->getStaticPath(),
            $this->config->get('static.target'),
            $this->config->get('static.exclude')
        );

        // copying assets in debug mode (for source maps)
        if ($this->builder->isDebug() && (bool) $this->config->get('assets.compile.sourcemap')) {
            // copying content of '<theme>/assets/' dir if exists
            if ($this->config->hasTheme()) {
                $themes = array_reverse($this->config->getTheme());
                foreach ($themes as $theme) {
                    $this->copy(
                        $this->config->getThemeDirPath($theme, 'assets')
                    );
                }
            }
            // copying content of 'assets/' dir if exists
            $this->copy(
                $this->config->getAssetsPath()
            );
        }

        if ($this->count === 0) {
            $this->builder->getLogger()->info('Nothing to copy');

            return 0;
        }
        $this->builder->getLogger()->info('Files copied', ['progress' => [$this->count, $this->count]]);
    }

    /**
     * Copying (mirror) files.
     */
    protected function copy(string $from, string $to = null, array $exclude = null): bool
    {
        if (Util\File::getFS()->exists($from)) {
            $finder = Finder::create()
                ->files()
                ->in($from);
            if (is_array($exclude)) {
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

            return true;
        }

        return false;
    }
}

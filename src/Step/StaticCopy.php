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

use Cecil\Util;
use Symfony\Component\Finder\Finder;

/**
 * Copying static files to site root.
 */
class StaticCopy extends AbstractStep
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
        Util::getFS()->remove($this->config->getOutputPath());
        Util::getFS()->mkdir($this->config->getOutputPath());

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
                    null,
                    $this->config->get('static.exclude')
                );
            }
        }

        // copying content of 'static/' dir if exists
        $this->copy(
            $this->builder->getConfig()->getStaticPath(),
            $this->config->get('static.target'),
            $this->config->get('static.exclude')
        );

        if ($this->count === 0) {
            $this->builder->getLogger()->info('Nothing to copy');

            return 0;
        }
        $this->builder->getLogger()->info('Files copied', ['progress' => [$this->count, $this->count]]);
    }

    /**
     * Copying (mirror) files.
     *
     * @param string      $from
     * @param string|null $to
     * @param array|null  $exclude
     *
     * @return bool
     */
    protected function copy(string $from, string $to = null, array $exclude = null): bool
    {
        if (Util::getFS()->exists($from)) {
            $finder = Finder::create()
                ->files()
                ->in($from);
            if (is_array($exclude)) {
                $finder->notName($exclude);
            }
            $this->count += $finder->count();
            Util::getFS()->mirror(
                $from,
                Util::joinFile($this->config->getOutputPath(), $to),
                $finder,
                ['override' => true]
            );

            return true;
        }

        return false;
    }
}

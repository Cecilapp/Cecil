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
    public function init($options)
    {
        if ($options['dry-run']) {
            $this->process = false;

            return;
        }

        // reset output directory
        Util::getFS()->remove($this->config->getOutputPath());
        Util::getFS()->mkdir($this->config->getOutputPath());

        $this->process = true;
    }

    /**
     * {@inheritdoc}
     */
    public function process()
    {
        call_user_func_array(
            $this->builder->getMessageCb(),
            ['COPY', 'Copying static']
        );

        // copying content of '<theme>/static/' dir if exists
        if ($this->config->hasTheme()) {
            $themes = array_reverse($this->config->getTheme());
            foreach ($themes as $theme) {
                $themeStaticDir = $this->config->getThemeDirPath($theme, 'static');
                $this->copy($themeStaticDir);
            }
        }

        // copying content of 'static/' dir if exists
        $staticDir = $this->builder->getConfig()->getStaticPath();
        $this->copy($staticDir, null, $this->config->get('static.exclude'));

        if ($this->count === 0) {
            call_user_func_array(
                $this->builder->getMessageCb(),
                ['COPY_PROGRESS', 'Nothing to copy']
            );

            return 0;
        }
        call_user_func_array(
            $this->builder->getMessageCb(),
            ['COPY_PROGRESS', 'Files copied', $this->count, $this->count]
        );
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
            $finder = new Finder();
            $finder->files()->in($from);
            if (is_array($exclude)) {
                $finder->notName($exclude);
            }
            $this->count += $finder->count();
            Util::getFS()->mirror(
                $from,
                $this->config->getOutputPath().(isset($to) ? '/'.$to : ''),
                $finder,
                ['override' => true]
            );

            return true;
        }

        return false;
    }
}

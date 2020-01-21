<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Step;

use Cecil\Util;
use Symfony\Component\Finder\Finder;

/**
 * Copy static directory content to site root.
 */
class StaticCopy extends AbstractStep
{
    /**
     * {@inheritdoc}
     */
    public function init($options)
    {
        // clean before
        Util::getFS()->remove($this->builder->getConfig()->getOutputPath());
        Util::getFS()->mkdir($this->builder->getConfig()->getOutputPath());

        $this->process = true;
    }

    /**
     * {@inheritdoc}
     */
    public function process()
    {
        $count = 0;

        call_user_func_array($this->builder->getMessageCb(), ['COPY', 'Copying static files']);
        // copy <theme>/static/ dir if exists
        if ($this->builder->getConfig()->hasTheme()) {
            $themes = array_reverse($this->builder->getConfig()->getTheme());
            foreach ($themes as $theme) {
                $themeStaticDir = $this->builder->getConfig()->getThemeDirPath($theme, 'static');
                if (Util::getFS()->exists($themeStaticDir)) {
                    $finder = new Finder();
                    $finder->files()->in($themeStaticDir);
                    $count += $finder->count();
                    Util::getFS()->mirror(
                        $themeStaticDir,
                        $this->builder->getConfig()->getOutputPath(),
                        null,
                        ['override' => true]
                    );
                }
            }
        }
        // copy static/ dir if exists
        $staticDir = $this->builder->getConfig()->getStaticPath();
        if (Util::getFS()->exists($staticDir)) {
            $finder = new Finder();
            $finder->files()->filter(function (\SplFileInfo $file) {
                return !(is_array($this->builder->getConfig()->get('static.exclude'))
                    && in_array($file->getBasename(), $this->builder->getConfig()->get('static.exclude')));
            })->in($staticDir);
            $count += $finder->count();
            Util::getFS()->mirror(
                $staticDir,
                $this->builder->getConfig()->getOutputPath(),
                $finder,
                ['override' => true]
            );
        }
        call_user_func_array($this->builder->getMessageCb(), ['COPY_PROGRESS', 'Start copy', 0, $count]);
        call_user_func_array($this->builder->getMessageCb(), ['COPY_PROGRESS', 'Files copied', $count, $count]);
    }
}

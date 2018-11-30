<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Step;

use PHPoole\Util;
use Symfony\Component\Finder\Finder;

/**
 * Copy static directory content to site root.
 */
class CopyStatic extends AbstractStep
{
    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function init($options)
    {
        // clean before
        Util::getFS()->remove($this->phpoole->getConfig()->getOutputPath());

        $this->process = true;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \PHPoole\Exception\Exception
     */
    public function process()
    {
        $count = 0;

        call_user_func_array($this->phpoole->getMessageCb(), ['COPY', 'Copying static files']);
        // copy <theme>/static/ dir if exists
        if ($this->phpoole->getConfig()->hasTheme()) {
            $themes = array_reverse($this->phpoole->getConfig()->getTheme());
            foreach ($themes as $theme) {
                $themeStaticDir = $this->phpoole->getConfig()->getThemeDirPath($theme, 'static');
                if (Util::getFS()->exists($themeStaticDir)) {
                    $finder = new Finder();
                    $finder->files()->in($themeStaticDir);
                    $count += $finder->count();
                    Util::getFS()->mirror(
                        $themeStaticDir,
                        $this->phpoole->getConfig()->getOutputPath(),
                        null,
                        ['override' => true]
                    );
                }
            }
        }
        // copy static/ dir if exists
        $staticDir = $this->phpoole->getConfig()->getStaticPath();
        if (Util::getFS()->exists($staticDir)) {
            $finder = new Finder();
            $finder->files()->filter(function (\SplFileInfo $file) {
                return !(is_array($this->phpoole->getConfig()->get('static.exclude'))
                    && in_array($file->getBasename(), $this->phpoole->getConfig()->get('static.exclude')));
            })->in($staticDir);
            $count += $finder->count();
            Util::getFS()->mirror(
                $staticDir,
                $this->phpoole->getConfig()->getOutputPath(),
                $finder,
                ['override' => true]
            );
        }
        call_user_func_array($this->phpoole->getMessageCb(), ['COPY_PROGRESS', 'Start copy', 0, $count]);
        call_user_func_array($this->phpoole->getMessageCb(), ['COPY_PROGRESS', 'Files copied', $count, $count]);
    }
}

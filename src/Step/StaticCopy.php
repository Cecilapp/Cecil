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
 * Copy static files to site root.
 */
class StaticCopy extends AbstractStep
{
    const TMP_DIR = '.cecil';

    /**
     * {@inheritdoc}
     */
    public function init($options)
    {
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
        $count = 0;

        call_user_func_array($this->builder->getMessageCb(), ['COPY', 'Copying static files']);

        // copy content of '<theme>/static/' dir if exists
        if ($this->config->hasTheme()) {
            $themes = array_reverse($this->config->getTheme());
            foreach ($themes as $theme) {
                $themeStaticDir = $this->config->getThemeDirPath($theme, 'static');
                if (Util::getFS()->exists($themeStaticDir)) {
                    $finder = new Finder();
                    $finder->files()->in($themeStaticDir);
                    $count += $finder->count();
                    Util::getFS()->mirror(
                        $themeStaticDir,
                        $this->config->getOutputPath(),
                        null,
                        ['override' => true]
                    );
                }
            }
        }
        // copy content of 'static/' dir if exists
        $staticDir = $this->config->getStaticPath();
        if (Util::getFS()->exists($staticDir)) {
            $exclude = $this->config->get('static.exclude');
            $finder = new Finder();
            $finder->files()->in($staticDir);
            if (is_array($exclude)) {
                $finder->files()->notName($exclude)->in($staticDir);
            }
            $count += $finder->count();
            Util::getFS()->mirror(
                $staticDir,
                $this->config->getOutputPath(),
                $finder,
                ['override' => true]
            );
        }
        // copy temporary images files
        $tmpDirImages = $this->config->getDestinationDir().'/'.self::TMP_DIR.'/images';
        if (Util::getFS()->exists($tmpDirImages)) {
            $finder = new Finder();
            $finder->files()->in($tmpDirImages);
            $count += $finder->count();
            Util::getFS()->mirror(
                $tmpDirImages,
                $this->config->getOutputPath().'/images',
                $finder,
                ['override' => true]
            );
            Util::getFS()->remove($tmpDirImages);
        }

        call_user_func_array($this->builder->getMessageCb(), ['COPY_PROGRESS', 'Start copy', 0, $count]);
        call_user_func_array($this->builder->getMessageCb(), ['COPY_PROGRESS', 'Files copied', $count, $count]);
    }
}

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
    private $count = 0;

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
        call_user_func_array($this->builder->getMessageCb(), ['COPY', 'Copying static files']);

        // copy content of '<theme>/static/' dir if exists
        if ($this->config->hasTheme()) {
            $themes = array_reverse($this->config->getTheme());
            foreach ($themes as $theme) {
                $themeStaticDir = $this->config->getThemeDirPath($theme, 'static');
                $this->copy($themeStaticDir);
            }
        }

        // copy content of 'static/' dir if exists
        $staticDir = $this->builder->getConfig()->getStaticPath();
        $this->copy($staticDir, null, $this->config->get('static.exclude'));

        // copy temporary images files
        $tmpDirImages = $this->config->getDestinationDir().'/'.self::TMP_DIR.'/images';
        if ($this->copy($tmpDirImages, 'images')) {
            Util::getFS()->remove($tmpDirImages);
        }

        call_user_func_array($this->builder->getMessageCb(), ['COPY_PROGRESS', 'Start copy', 0, $this->count]);
        call_user_func_array($this->builder->getMessageCb(), ['COPY_PROGRESS', 'Copied', $this->count, $this->count]);
    }

    /**
     * Copy (mirror) files.
     *
     * @param string      $from
     * @param string|null $to
     * @param array       $exclude
     *
     * @return bool
     */
    private function copy(string $from, string $to = null, array $exclude = []): bool
    {
        if (Util::getFS()->exists($from)) {
            $finder = new Finder();
            $finder->files()->in($from);
            if (is_array($this->config->get('static.exclude'))) {
                $finder->files()->notName($this->config->get('static.exclude'))->in($from);
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

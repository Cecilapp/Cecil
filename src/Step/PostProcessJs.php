<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Step;

use Cecil\Util;
use MatthiasMullie\Minify;

/**
 * Post process JS files.
 */
class PostProcessJs extends AbstractPostProcess
{
    /**
     * {@inheritdoc}
     */
    public function init(array $options)
    {
        $this->type = 'js';
        parent::init($options);
    }

    /**
     * {@inheritdoc}
     */
    public function setProcessor()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function processFile()
    {
        $cachedFile = Util::joinFile([$this->config->getCachePath(), $this->inputFile->getRelativePathname()]);

        if (!Util::getFS()->exists($cachedFile)) {
            $minifier = new Minify\JS($this->inputFile->getPathname());
            $minified = $minifier->minify();
            \Cecil\Util::getFS()->dumpFile($cachedFile, $minified);
        }

        $this->outputFile = new \SplFileInfo($cachedFile);
    }
}

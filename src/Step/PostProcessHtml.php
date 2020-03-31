<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Step;

use Cecil\Util;
use voku\helper\HtmlMin;

/**
 * Post process HTML files.
 */
class PostProcessHtml extends AbstractPostProcess
{
    /**
     * {@inheritdoc}
     */
    public function init(array $options)
    {
        $this->type = 'html';
        parent::init($options);
    }

    /**
     * {@inheritdoc}
     */
    public function setProcessor()
    {
        $this->type = 'html';
        $this->processor = new HtmlMin();
    }

    /**
     * {@inheritdoc}
     */
    public function processFile()
    {
        $cachedFile = Util::joinFile([$this->config->getCachePath(), $this->inputFile->getRelativePathname()]);

        if (!Util::getFS()->exists($cachedFile)) {
            $html = file_get_contents($this->inputFile->getPathname());
            $htmlCompressed = $this->processor->minify($html);
            \Cecil\Util::getFS()->dumpFile($cachedFile, $htmlCompressed);
        }

        $this->outputFile = new \SplFileInfo($cachedFile);
    }
}

<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Step;

use WyriHaximus\HtmlCompress\Factory as Compressor;

/**
 * HTML files Optimization.
 */
class OptimizeHtml extends AbstractStepOptimize
{
    public function setProcessor()
    {
        $this->type = 'html';
        $this->processor = Compressor::construct();
    }

    public function processFile(\Symfony\Component\Finder\SplFileInfo $file)
    {
        $html = file_get_contents($file->getPathname());
        $htmlCompressed = $this->processor->compress($html);
        \Cecil\Util::getFS()->dumpFile($file->getPathname(), $htmlCompressed);
    }
}

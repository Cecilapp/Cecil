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

use voku\helper\HtmlMin;

/**
 * Post process HTML files.
 */
class PostProcessHtml extends AbstractPostProcess
{
    /**
     * {@inheritdoc}
     */
    public function init($options)
    {
        $this->type = 'html';
        parent::init($options);
    }

    /**
     * {@inheritdoc}
     */
    public function setProcessor()
    {
        $this->processor = new HtmlMin();
    }

    /**
     * {@inheritdoc}
     */
    public function processFile(\Symfony\Component\Finder\SplFileInfo $file)
    {
        $html = \Cecil\Util::fileGetContents($file->getPathname());
        $minified = $this->processor->minify($html);
        \Cecil\Util::getFS()->dumpFile($file->getPathname(), $minified);
    }
}

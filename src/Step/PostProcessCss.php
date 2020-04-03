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

use MatthiasMullie\Minify;

/**
 * Post process CSS files.
 */
class PostProcessCss extends AbstractPostProcess
{
    /**
     * {@inheritdoc}
     */
    public function init($options)
    {
        $this->type = 'css';
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
    public function processFile(\Symfony\Component\Finder\SplFileInfo $file)
    {
        $minifier = new Minify\CSS($file->getPathname());
        $minified = $minifier->minify();
        \Cecil\Util::getFS()->dumpFile($file->getPathname(), $minified);
    }
}

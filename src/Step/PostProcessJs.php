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
 * Post process JS files.
 */
class PostProcessJs extends AbstractPostProcess
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'Post-processing JS';
    }

    /**
     * {@inheritdoc}
     */
    public function init($options)
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
    public function processFile(\Symfony\Component\Finder\SplFileInfo $file): string
    {
        $minifier = new Minify\JS($file->getPathname());

        return $minifier->minify();
    }
}

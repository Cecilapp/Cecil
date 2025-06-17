<?php

declare(strict_types=1);

/*
 * This file is part of Cecil.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Step\Optimize;

use MatthiasMullie\Minify;

/**
 * JavaScript optimization step.
 *
 * This class extends the AbstractOptimize class and provides functionality
 * to optimize JavaScript files. It uses the MatthiasMullie\Minify library to
 * minify JavaScript files, reducing their size and improving load times.
 * It initializes with the type 'js' and processes files by minifying them.
 */
class Js extends AbstractOptimize
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'Optimizing JS';
    }

    /**
     * {@inheritdoc}
     */
    public function init(array $options): void
    {
        $this->type = 'js';
        parent::init($options);
    }

    /**
     * {@inheritdoc}
     */
    public function setProcessor(): void
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

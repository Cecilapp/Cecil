<?php

/**
 * This file is part of Cecil.
 *
 * (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cecil\Step\Optimize;

use MatthiasMullie\Minify;

/**
 * CSS optimization step.
 *
 * This class extends the AbstractOptimize class and provides functionality
 * to optimize CSS files. It uses the MatthiasMullie\Minify library to
 * minify CSS files, reducing their size and improving load times.
 * It initializes with the type 'css' and processes files by minifying them.
 */
class Css extends AbstractOptimize
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'Optimizing CSS';
    }

    /**
     * {@inheritdoc}
     */
    public function init(array $options): void
    {
        $this->type = 'css';
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
        $minifier = new Minify\CSS($file->getPathname());

        return $minifier->minify();
    }
}

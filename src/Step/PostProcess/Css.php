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

namespace Cecil\Step\PostProcess;

use MatthiasMullie\Minify;

/**
 * Post process CSS files.
 */
class Css extends AbstractPostProcess
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'Post-processing CSS';
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

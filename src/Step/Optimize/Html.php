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

use Cecil\Util;
use voku\helper\HtmlMin;

/**
 * Optimize HTML files.
 */
class Html extends AbstractOptimize
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'Optimizing HTML';
    }

    /**
     * {@inheritdoc}
     */
    public function init(array $options): void
    {
        $this->type = 'html';
        // https://github.com/voku/HtmlMin/issues/93
        if (version_compare(PHP_VERSION, '8.3.0', '>=')) {
            $this->builder->getLogger()->debug("{$this->getName()} is disabled for PHP 8.3");

            return;
        }
        parent::init($options);
    }

    /**
     * {@inheritdoc}
     */
    public function setProcessor(): void
    {
        $this->processor = new HtmlMin();
    }

    /**
     * {@inheritdoc}
     */
    public function processFile(\Symfony\Component\Finder\SplFileInfo $file): string
    {
        $html = Util\File::fileGetContents($file->getPathname());

        return $this->processor->minify($html);
    }

    /**
     * {@inheritdoc}
     */
    public function encode(?string $content = null): ?string
    {
        return json_encode($content);
    }

    /**
     * {@inheritdoc}
     */
    public function decode(?string $content = null): ?string
    {
        return json_decode((string) $content);
    }
}

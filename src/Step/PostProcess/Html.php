<?php

/*
 * This file is part of Cecil.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Step\PostProcess;

use Cecil\Util;
use voku\helper\HtmlMin;

/**
 * Post process HTML files.
 */
class Html extends AbstractPostProcess
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'Post-processing HTML';
    }

    /**
     * {@inheritdoc}
     */
    public function init(array $options): void
    {
        $this->type = 'html';
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
    public function encode(string $content = null): ?string
    {
        return json_encode($content);
    }

    /**
     * {@inheritdoc}
     */
    public function decode(string $content = null): ?string
    {
        return json_decode((string) $content);
    }
}

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

use Spatie\ImageOptimizer\OptimizerChainFactory as Optimizer;

/**
 * Post process image files.
 */
class PostProcessImages extends AbstractPostProcess
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'Post-processing images';
    }

    /**
     * {@inheritdoc}
     */
    public function init($options)
    {
        $this->type = 'images';
        parent::init($options);
    }

    /**
     * {@inheritdoc}
     */
    public function setProcessor(): void
    {
        $this->processor = Optimizer::create();
    }

    /**
     * {@inheritdoc}
     */
    public function processFile(\Symfony\Component\Finder\SplFileInfo $file): string
    {
        /** @var Optimizer $processor */
        $this->processor->optimize($file->getPathname());

        return $file->getContents();
    }

    /**
     * {@inheritdoc}
     */
    public function encode(string $content = null): ?string
    {
        return base64_encode((string) $content);
    }

    /**
     * {@inheritdoc}
     */
    public function decode(string $content = null): ?string
    {
        return base64_decode((string) $content);
    }
}

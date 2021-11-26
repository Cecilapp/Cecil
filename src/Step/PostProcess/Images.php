<?php
/**
 * This file is part of the Cecil/Cecil package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Step\PostProcess;

use Cecil\Assets\Image;

/**
 * Post process image files.
 */
class Images extends AbstractPostProcess
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
        $this->processor = Image::optimizer($this->config->get('assets.images.quality'));
    }

    /**
     * {@inheritdoc}
     */
    public function processFile(\Symfony\Component\Finder\SplFileInfo $file): string
    {
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

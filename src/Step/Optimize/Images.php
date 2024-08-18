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

use Cecil\Assets\Image\Optimizer;

/**
 * Optimize image files.
 */
class Images extends AbstractOptimize
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'Optimizing images';
    }

    /**
     * {@inheritdoc}
     */
    public function init(array $options): void
    {
        $this->type = 'images';
        parent::init($options);
    }

    /**
     * {@inheritdoc}
     */
    public function setProcessor(): void
    {
        $this->processor = Optimizer::create($this->config->get('assets.images.quality') ?? 75);
    }

    /**
     * {@inheritdoc}
     */
    public function processFile(\Symfony\Component\Finder\SplFileInfo $file): string
    {
        try {
            $this->processor->optimize($file->getPathname());
        } catch (\Exception $e) {
            $this->builder->getLogger()->error(\sprintf('Can\'t optimize image "%s": "%s"', $file->getPathname(), $e->getMessage()));
        }

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

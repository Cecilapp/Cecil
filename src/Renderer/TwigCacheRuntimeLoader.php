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

namespace Cecil\Renderer;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Twig\Extra\Cache\CacheRuntime;
use Twig\RuntimeLoader\RuntimeLoaderInterface;

class TwigCacheRuntimeLoader implements RuntimeLoaderInterface
{
    protected string $cacheDir;

    public function __construct(string $cacheDir)
    {
        $this->cacheDir = $cacheDir;
    }

    public function load(string $class)
    {
        if (CacheRuntime::class === $class) {
            return new CacheRuntime(new TagAwareAdapter(new FilesystemAdapter(namespace: '_fragments', directory: $this->cacheDir)));
        }

        return null;
    }
}

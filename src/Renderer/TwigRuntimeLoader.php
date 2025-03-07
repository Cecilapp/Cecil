<?php

declare(strict_types=1);

namespace Cecil\Renderer;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Twig\Extra\Cache\CacheRuntime;
use Twig\RuntimeLoader\RuntimeLoaderInterface;

class TwigRuntimeLoader implements RuntimeLoaderInterface
{
    protected string $cacheDir;

    public function __construct(string $cacheDir)
    {
        $this->cacheDir = $cacheDir;
    }

    public function load(string $class)
    {
        if (CacheRuntime::class === $class) { // @phpstan-ignore-line
            return new CacheRuntime(new TagAwareAdapter(new FilesystemAdapter(namespace: '_fragments', directory: $this->cacheDir)));
        }

        return null;
    }
}

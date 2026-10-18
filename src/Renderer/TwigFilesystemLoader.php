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

namespace Cecil\Renderer;

use Twig\Loader\FilesystemLoader;

/**
 * TwigFilesystemLoader class.
 *
 * Twig filesystem loader whose cache keys include a "compilation key",
 * describing the options that change how templates are compiled
 * (e.g. the autoescape strategy) but that Twig does not take into account
 * to name compiled templates classes (and so cached files).
 */
class TwigFilesystemLoader extends FilesystemLoader
{
    /**
     * Key appended to templates cache keys.
     * @var string
     */
    protected string $compilationKey;

    /**
     * @param string|string[] $paths A path or an array of paths where to look for templates
     */
    public function __construct($paths, string $compilationKey)
    {
        parent::__construct($paths);
        $this->compilationKey = $compilationKey;
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function getCacheKey(string $name): string
    {
        return parent::getCacheKey($name) . '#' . $this->compilationKey;
    }
}

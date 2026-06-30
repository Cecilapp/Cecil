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

namespace Cecil\Asset;

use Cecil\Builder;
use Cecil\Config;
use Cecil\Exception\RuntimeException;
use Cecil\Util\ImageOptimizer;
use MatthiasMullie\Minify;

/**
 * Minifies CSS/JS assets and optimizes image files.
 */
class Optimizer
{
    /** @var Builder */
    protected $builder;

    /** @var Config */
    protected $config;

    public function __construct(Builder $builder)
    {
        $this->builder = $builder;
        $this->config = $builder->getConfig();
    }

    /**
     * Minifies CSS or JS content in the given data array.
     * Returns the updated data array, unchanged if conditions are not met.
     *
     * @param array $data Asset data array (must contain 'ext', 'path', 'content')
     *
     * @return array Updated data array with minified content
     *
     * @throws RuntimeException
     */
    public function minify(array $data): array
    {
        // abort if already minified
        if (substr($data['path'], -8) === '.min.css' || substr($data['path'], -7) === '.min.js') {
            return $data;
        }
        // abort if not a CSS or JS file
        if (!\in_array($data['ext'], ['css', 'js'])) {
            return $data;
        }
        // in debug mode: disable minify to preserve inline source map
        if ($this->builder->isDebug() && $this->config->isEnabled('assets.compile.sourcemap')) {
            return $data;
        }

        switch ($data['ext']) {
            case 'css':
                $minifier = new Minify\CSS($data['content']);
                break;
            case 'js':
                $minifier = new Minify\JS($data['content']);
                break;
            default:
                throw new RuntimeException(\sprintf('Unable to minify "%s".', $data['path']));
        }
        $data['content'] = $minifier->minify();
        $data['size'] = \strlen($data['content']);

        $this->builder->getLogger()->debug(\sprintf('Asset minified: "%s"', $data['path']));

        return $data;
    }

    /**
     * Optimizes an image file in-place.
     * Returns the new file size in bytes.
     */
    public function optimizeImage(string $filepath, string $path, int $quality): int
    {
        $message = \sprintf('Asset not optimized: "%s"', $path);
        $sizeBefore = filesize($filepath);
        ImageOptimizer::create($quality)
            ->throws(function (\Throwable $exception) use ($path) {
                $this->builder->getLogger()->debug(\sprintf('Error optimizing image "%s": %s', $path, $exception->getMessage()));
            })
            ->optimize($filepath);
        $sizeAfter = filesize($filepath);
        if ($sizeAfter < $sizeBefore) {
            $message = \sprintf('Asset optimized: "%s" (%s Ko -> %s Ko)', $path, ceil($sizeBefore / 1000), ceil($sizeAfter / 1000));
        }
        $this->builder->getLogger()->debug($message);

        return (int) $sizeAfter;
    }
}

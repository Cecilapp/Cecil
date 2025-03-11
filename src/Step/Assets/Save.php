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

namespace Cecil\Step\Assets;

use Cecil\Assets\Cache;
use Cecil\Exception\RuntimeException;
use Cecil\Step\AbstractStep;
use Cecil\Util;

/**
 * Assets saving.
 */
class Save extends AbstractStep
{
    protected Cache $cache;
    protected string $cacheKey;

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'Saving assets';
    }

    /**
     * {@inheritdoc}
     */
    public function init(array $options): void
    {
        // should clear cache?
        $this->clearCache();

        if ($options['dry-run']) {
            return;
        }

        $this->cache = new \Cecil\Assets\Cache($this->builder, (string) $this->config->get('cache.assets.dir'));
        $this->cacheKey = \sprintf('_list__%s', $this->builder->getVersion());
        if (empty($this->assets) && $this->cache->has($this->cacheKey)) {
            $this->builder->setAssets($this->cache->get($this->cacheKey));
        }

        $this->canProcess = true;
    }

    /**
     * {@inheritdoc}
     * Note: a file from `static/` with the same name will NOT be overridden.
     *
     * @throws RuntimeException
     */
    public function process(): void
    {
        $total = \count($this->builder->getAssets());
        $count = 0;
        foreach ($this->builder->getAssets() as $path) {
            $count++;
            Util\File::getFS()->copy($this->cache->getContentFilePathname($path), Util::joinFile($this->config->getOutputPath(), $path), false);
            $message = \sprintf('Asset "%s" saved', $path);
            $this->builder->getLogger()->info($message, ['progress' => [$count, $total]]);
        }
        $this->cache->set($this->cacheKey, $this->builder->getAssets());
    }

    /**
     * Deletes cache directory.
     */
    private function clearCache(): void
    {
        if ($this->config->get('cache.enabled') === false) {
            try {
                Util\File::getFS()->remove($this->config->getCachePath());
            } catch (\Exception) {
                throw new RuntimeException(\sprintf('Can\'t remove cache directory "%s".', $this->config->getCachePath()));
            }
        }
    }
}

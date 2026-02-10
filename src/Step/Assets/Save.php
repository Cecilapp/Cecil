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

namespace Cecil\Step\Assets;

use Cecil\Cache;
use Cecil\Exception\RuntimeException;
use Cecil\Step\AbstractStep;
use Cecil\Util;

/**
 * Save assets step.
 *
 * This step is responsible for saving assets to the output directory.
 * It copies files from the cache to the output directory, ensuring that
 * assets are available for the final build. If the cache is disabled, it
 * clears the cache directory before processing assets.
 */
class Save extends AbstractStep
{
    protected Cache $cache;

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
        // last build step: should clear cache?
        $this->clearCacheIfDisabled();

        if ($options['dry-run']) {
            return;
        }

        $this->cache = new Cache($this->builder, 'assets');

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
        $total = \count($this->builder->getAssetsList());
        if ($total > 0) {
            $count = 0;
            foreach ($this->builder->getAssetsList() as $path) {
                // if file deleted from assets cache
                if (!Util\File::getFS()->exists($this->cache->getContentFile($path))) {
                    $this->builder->getLogger()->warning(\sprintf('Asset "%s" not found in cache, skipping. You should clear all cache.', $path));
                    break;
                }
                $count++;
                Util\File::getFS()->copy($this->cache->getContentFile($path), Util::joinFile($this->config->getOutputPath(), $path), false);
                $message = \sprintf('Asset "%s" saved', $path);
                $this->builder->getLogger()->info($message, ['progress' => [$count, $total]]);
            }
            $this->builder->deleteAssetsList();
        }
    }

    /**
     * Deletes cache directory, if cache is disabled.
     */
    private function clearCacheIfDisabled(): void
    {
        if (!$this->config->isEnabled('cache')) {
            try {
                Util\File::getFS()->remove($this->config->getCachePath());
            } catch (\Exception) {
                throw new RuntimeException(\sprintf('Unable to remove cache directory "%s".', $this->config->getCachePath()));
            }
        }
    }
}

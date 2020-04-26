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

use Cecil\Util;

/**
 * Copying assets files.
 */
class AssetsCopy extends StaticCopy
{
    protected $count = 0;

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'Copying assets';
    }

    /**
     * {@inheritdoc}
     */
    public function init($options)
    {
        if ($options['dry-run']) {
            $this->canProcess = false;

            return;
        }

        $this->canProcess = true;
    }

    /**
     * {@inheritdoc}
     */
    public function process()
    {
        $this->copy(Util::joinFile($this->config->getCachePath(), 'assets'), '');

        // deletes cache?
        if ((bool) $this->config->get('cache.enabled') === false) {
            if (!empty($this->config->getCachePath()) && is_dir($this->config->getCachePath())) {
                Util::getFS()->remove($this->config->getCachePath());
            }
        }

        if ($this->count === 0) {
            $this->builder->getLogger()->info('Nothing to copy');

            return 0;
        }
        $this->builder->getLogger()->info('Files copied', ['progress' => [$this->count, $this->count]]);
    }
}

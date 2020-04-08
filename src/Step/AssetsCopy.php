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
    public function init($options)
    {
        if ($options['dry-run']) {
            $this->process = false;

            return;
        }

        $this->process = true;
    }

    /**
     * {@inheritdoc}
     */
    public function process()
    {
        call_user_func_array(
            $this->builder->getMessageCb(),
            ['COPY', 'Copying assets']
        );

        $this->copy(Util::joinFile($this->config->getCachePath(), 'assets'), '');

        // deletes cache?
        if ((bool) $this->config->get('cache.enabled') === false) {
            Util::getFS()->remove($this->config->getCachePath());
        }

        if ($this->count === 0) {
            call_user_func_array(
                $this->builder->getMessageCb(),
                ['COPY_PROGRESS', 'Nothing to copy']
            );

            return 0;
        }
        call_user_func_array(
            $this->builder->getMessageCb(),
            ['COPY_PROGRESS', 'Files copied', $this->count, $this->count]
        );
    }
}

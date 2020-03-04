<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Step;

use Cecil\Util;

/**
 * Copy assets files.
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

        $cacheDirImages = $this->config->getCachePath().'/'.(string) $this->config->get('cache.images.dir');
        if ($this->copy($cacheDirImages, 'images')) {
            if ((bool) $this->config->get('cache.enabled') === false) {
                Util::getFS()->remove($cacheDirImages);
            }
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

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
 * Copy cached files.
 */
class CacheCopy extends StaticCopy
{
    protected $count = 0;

    /**
     * {@inheritdoc}
     */
    public function init($options)
    {
        $cacheDir = $this->config->getDestinationDir().'/'.$this->config->get('cache.dir');
        if ($this->config->get('cache.external')) {
            $cacheDir = $this->config->get('cache.dir');
        }
        if (Util::getFS()->exists($cacheDir)) {
            $this->process = true;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function process()
    {
        call_user_func_array($this->builder->getMessageCb(), ['COPY', 'Copying cache']);

        $cacheDirImages = $this->config->getDestinationDir().'/'.$this->config->get('cache.dir').'/'.$this->config->get('cache.images.dir');
        if ($this->config->get('cache.external')) {
            $cacheDirImages = $this->config->get('cache.dir').'/'.$this->config->get('cache.images.dir');
        }
        if ($this->copy($cacheDirImages, 'images')) {
            if ((bool) $this->config->get('cache.enabled') === false) {
                Util::getFS()->remove($cacheDirImages);
            }
        }

        call_user_func_array($this->builder->getMessageCb(), ['COPY_PROGRESS', 'Start copy', 0, $this->count]);
        call_user_func_array($this->builder->getMessageCb(), ['COPY_PROGRESS', 'Copied', $this->count, $this->count]);
    }
}

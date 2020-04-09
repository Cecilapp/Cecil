<?php
/**
 * This file is part of the Cecil/Cecil package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Assets;

use Cecil\Builder;
use Cecil\Config;

abstract class AbstractAsset
{
    /** @var Builder */
    protected $builder;
    /** @var Config */
    protected $config;

    const CACHE_ASSETS_DIR = 'assets';

    /**
     * @var Builder
     */
    public function __construct(Builder $builder)
    {
        $this->builder = $builder;
        $this->config = $builder->getConfig();
    }
}

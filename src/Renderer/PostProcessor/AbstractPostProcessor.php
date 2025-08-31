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

namespace Cecil\Renderer\PostProcessor;

use Cecil\Builder;

/**
 * PostProcessor Abstract class.
 */
abstract class AbstractPostProcessor implements PostProcessorInterface
{
    /** @var Builder */
    protected $builder;

    /** @var \Cecil\Config */
    protected $config;

    /**
     * {@inheritdoc}
     */
    public function __construct(Builder $builder)
    {
        $this->builder = $builder;
        $this->config = $builder->getConfig();
    }
}

<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Step;

use Cecil\Builder;
use Cecil\Config;

abstract class AbstractStep implements StepInterface
{
    /**
     * @var Builder
     */
    protected $builder;
    /**
     * @var Config
     */
    protected $config;
    /**
     * @var bool
     */
    protected $process = false;

    /**
     * {@inheritdoc}
     */
    public function __construct(Builder $builder)
    {
        $this->builder = $builder;
        $this->config = $builder->getConfig();
    }

    /**
     * {@inheritdoc}
     */
    public function init($options)
    {
        $this->process = true;
    }

    /**
     * {@inheritdoc}
     */
    public function runProcess()
    {
        if ($this->process) {
            $this->process();
        }
    }

    /**
     * {@inheritdoc}
     */
    abstract public function process();
}

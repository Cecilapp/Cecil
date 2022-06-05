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

namespace Cecil\Step;

use Cecil\Builder;
use Cecil\Config;

abstract class AbstractStep implements StepInterface
{
    /** @var Builder */
    protected $builder;

    /** @var Config */
    protected $config;

    /** @var array Build options. */
    protected $options;

    /** @var bool */
    protected $canProcess = false;

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
    public function init(array $options): void
    {
        $this->options = $options;
        $this->canProcess = true;
    }

    /**
     * {@inheritdoc}
     */
    public function canProcess(): bool
    {
        return $this->canProcess;
    }

    /**
     * {@inheritdoc}
     */
    abstract public function process(): void;
}

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

namespace Cecil\Step;

use Cecil\Builder;
use Cecil\Config;

/**
 * Abstract step class.
 *
 * This class provides a base implementation for steps in the build process.
 * It implements the StepInterface and provides common functionality such as
 * initialization, checking if the step can be processed, and a constructor
 * that accepts a Builder instance.
 */
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

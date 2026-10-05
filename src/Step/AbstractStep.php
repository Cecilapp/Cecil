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

use Cecil\BuildContextInterface;
use Cecil\Builder;
use Cecil\Config;
use Cecil\Exception\RuntimeException;

/**
 * Abstract step class.
 *
 * This class provides a base implementation for steps in the build process.
 * It implements the StepInterface and provides common functionality such as
 * initialization, checking if the step can be processed, and a constructor
 * that accepts a build context instance.
 */
abstract class AbstractStep implements StepInterface
{
    /** @var BuildContextInterface */
    protected $builder;

    /** @var Config */
    protected $config;

    /**
     * Configuration options for the step (see \Cecil\Builder::OPTIONS).
     * @var array<string, mixed>
     */
    protected $options;

    /** @var bool */
    protected $canProcess = false;

    /**
     * {@inheritdoc}
     */
    public function __construct(BuildContextInterface $builder)
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
     *
     * If init() is used, true by default.
     */
    public function canProcess(): bool
    {
        return $this->canProcess;
    }

    /**
     * Returns the concrete Builder, required by components that depend on more than BuildContextInterface.
     *
     * @throws RuntimeException
     */
    protected function getBuilder(): Builder
    {
        if (!$this->builder instanceof Builder) {
            throw new RuntimeException(\sprintf('"%s" requires an instance of "%s".', static::class, Builder::class));
        }

        return $this->builder;
    }

    /**
     * {@inheritdoc}
     */
    abstract public function process(): void;
}

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
use Psr\Log\LoggerInterface;

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

    /** @var LoggerInterface */
    protected $logger;

    /**
     * Configuration options for the step.
     * @var Builder::OPTIONS
     */
    protected $options;

    /** @var bool */
    protected $canProcess = false;

    /**
     * Flexible constructor supporting dependency injection or legacy mode.
     */
    public function __construct(Builder $builder, ?Config $config = null, ?LoggerInterface $logger = null)
    {
        $this->builder = $builder;
        $this->config = $config ?? $builder->getConfig();
        $this->logger = $logger ?? $builder->getLogger();
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
     * {@inheritdoc}
     */
    abstract public function process(): void;
}

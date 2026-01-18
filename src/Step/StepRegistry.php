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
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Step Registry for managing build steps with dependency injection.
 *
 * This registry provides a centralized way to manage build steps
 * by retrieving them from the DI container when available.
 */
class StepRegistry
{
    /**
     * @var ContainerInterface
     */
    private ContainerInterface $container;

    /**
     * @var Builder
     */
    private Builder $builder;

    /**
     * Constructor.
     *
     * @param Builder $builder The builder instance
     * @param ContainerInterface $container DI container
     */
    public function __construct(Builder $builder, ContainerInterface $container)
    {
        $this->builder = $builder;
        $this->container = $container;
    }

    /**
     * Creates a step instance.
     *
     * Tries to retrieve the step from the container first,
     * otherwise instantiates it directly.
     *
     * @param string $stepClass The step class name
     * @return StepInterface The step instance
     */
    public function createStep(string $stepClass): StepInterface
    {
        // Try to get from container if registered
        if ($this->container->has($stepClass)) {
            $step = $this->container->get($stepClass);
            // Set builder for DI-injected steps (they may have other dependencies injected)
            if (method_exists($step, 'setBuilder')) {
                $step->setBuilder($this->builder);
            }
            return $step;
        }

        // Fallback to direct instantiation
        return new $stepClass($this->builder);
    }

    /**
     * Gets all steps for the build process.
     *
     * @param array $options Build options
     * @return StepInterface[] Array of initialized and processable steps
     */
    public function getSteps(array $options): array
    {
        $steps = [];

        foreach (Builder::STEPS as $stepClass) {
            $step = $this->createStep($stepClass);
            $step->init($options);

            if ($step->canProcess()) {
                $steps[] = $step;
            }
        }

        return $steps;
    }
}

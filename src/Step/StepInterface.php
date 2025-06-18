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

/**
 * Step interface.
 *
 * This interface defines the methods that any step in the build process must implement.
 * Steps are used to perform specific actions during the build process, such as generating
 * pages, processing data, or applying transformations.
 */
interface StepInterface
{
    /**
     * StepInterface constructor.
     */
    public function __construct(Builder $builder);

    /**
     * Returns the step name.
     */
    public function getName(): string;

    /**
     * This method is called before the step is processed to initialize
     * the step with necessary options and to determine if it can be executed.
     * @param array<Builder::OPTIONS> $options
     */
    public function init(array $options): void;

    /**
     * Method to know if the step can be processed.
     */
    public function canProcess(): bool;

    /**
     * Process implementation.
     */
    public function process(): void;
}

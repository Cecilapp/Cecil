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
     * Checks if the step can be processed.
     */
    public function init(array $options): void;

    /**
     * Can step be processed?
     */
    public function canProcess(): bool;

    /**
     * Process implementation.
     */
    public function process(): void;
}

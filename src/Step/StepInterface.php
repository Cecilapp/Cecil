<?php
/**
 * This file is part of the Cecil/Cecil package.
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
     *
     * @param Builder $builder
     */
    public function __construct(Builder $builder);

    /**
     * Returns step name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Checks if the step can be processed.
     *
     * @param array $options
     *
     * @return void
     */
    public function init($options);

    /**
     * Can step be processed?
     *
     * @return bool
     */
    public function canProcess(): bool;

    /**
     * Process implementation.
     *
     * @return void
     */
    public function process();
}

<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
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
     * Checks if step can be processed.
     *
     * @param array
     *
     * @return void
     */
    public function init(array $options);

    /**
     * Public call to process.
     *
     * @return void
     */
    public function runProcess();

    /**
     * Process implementation.
     *
     * @return void
     */
    public function process();
}

<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Step;

use PHPoole\Builder;

interface StepInterface
{
    /**
     * StepInterface constructor.
     *
     * @param Builder $phpoole
     *
     * @return void
     */
    public function __construct(Builder $phpoole);

    /**
     * Checks if step can be processed.
     *
     * @param array
     *
     * @return void
     */
    public function init($options);

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

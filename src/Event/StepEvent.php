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

namespace Cecil\Event;

use Cecil\BuildContextInterface;
use Cecil\Step\StepInterface;

/**
 * Step event.
 */
class StepEvent extends BuildEvent
{
    public function __construct(BuildContextInterface $builder, protected StepInterface $step)
    {
        parent::__construct($builder);
    }

    /**
     * Returns the step.
     */
    public function getStep(): StepInterface
    {
        return $this->step;
    }
}

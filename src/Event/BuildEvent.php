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
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Build event.
 *
 * Gives access to the build context (configuration, pages, data, etc.) and to the build options.
 */
class BuildEvent extends Event
{
    public function __construct(protected BuildContextInterface $builder)
    {
    }

    /**
     * Returns the build context.
     */
    public function getBuilder(): BuildContextInterface
    {
        return $this->builder;
    }

    /**
     * Returns the build options.
     *
     * @return array<string, mixed>
     *
     * @see \Cecil\Builder::OPTIONS
     */
    public function getOptions(): array
    {
        return $this->builder->getBuildOptions();
    }
}

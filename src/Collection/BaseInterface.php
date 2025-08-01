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

namespace Cecil\Collection;

/**
 * Base interface.
 *
 * Defines the basic structure for collections that require a unique identifier.
 */
interface BaseInterface
{
    /**
     * Set the unique identifier.
     */
    public function setId(string $id): self;

    /**
     * Return the unique identifier.
     */
    public function getId(): string;
}

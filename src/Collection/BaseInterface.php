<?php
/**
 * This file is part of the Cecil/Cecil package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Collection;

/**
 * Base interface.
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

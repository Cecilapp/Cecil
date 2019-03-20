<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
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
     *
     * @param string $id
     *
     * @return self
     */
    public function setId(string $id): self;

    /**
     * Return the unique identifier.
     *
     * @return string
     */
    public function getId(): string;
}

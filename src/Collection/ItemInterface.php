<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Collection;

/**
 * Interface ItemInterface.
 */
interface ItemInterface extends \ArrayAccess
{
    /**
     * Set the item's identifier.
     *
     * @param string|null $id
     *
     * @return self
     */
    public function setId(string $id = null);

    /**
     * Return the item's identifier.
     *
     * @return string
     */
    public function getId(): string;
}

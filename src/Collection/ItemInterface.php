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
interface ItemInterface extends BaseInterface, \ArrayAccess
{
    /**
     * Set the item's identifier.
     *
     * @param string $id
     *
     * @return self
     */
    public function setId(string $id): BaseInterface;

    /**
     * Return the item's identifier.
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Return collection as array.
     *
     * @return array
     */
    public function toArray(): array;
}

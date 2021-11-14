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
 * Interface ItemInterface.
 */
interface ItemInterface extends BaseInterface, \ArrayAccess
{
    /**
     * Set the item's identifier.
     */
    public function setId(string $id): BaseInterface;

    /**
     * Returns the item's identifier.
     */
    public function getId(): string;

    /**
     * Returns collection as array.
     */
    public function toArray(): array;
}

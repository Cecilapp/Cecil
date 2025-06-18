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
 * Item interface.
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
     * Returns properties as array.
     */
    public function toArray(): array;
}

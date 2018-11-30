<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Collection;

/**
 * Interface ItemInterface.
 */
interface ItemInterface extends \ArrayAccess
{
    /**
     * Set the item identifier.
     *
     * @param string|null $id
     *
     * @return self
     */
    public function setId($id = null);

    /**
     * Return the item identifier.
     *
     * @return string
     */
    public function getId();
}

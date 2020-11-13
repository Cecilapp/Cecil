<?php
/**
 * This file is part of the Cecil/Cecil package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Collection\Menu;

use Cecil\Collection\Item;

/**
 * Class Entry.
 */
class Entry extends Item
{
    /**
     * Set the menu entry name.
     *
     * @param string $value
     *
     * @return self
     */
    public function setName(string $value): self
    {
        $this->offsetSet('name', $value);

        return $this;
    }

    /**
     * Set the menu entry URL.
     *
     * @param string $value
     *
     * @return self
     */
    public function setUrl(string $value = null): self
    {
        $this->offsetSet('url', $value);

        return $this;
    }

    /**
     * Set menu entry weight.
     *
     * @param string $value
     *
     * @return self
     */
    public function setWeight(string $value): self
    {
        $this->offsetSet('weight', $value);

        return $this;
    }

    /**
     * Get menu entry weight.
     *
     * @return int|null
     */
    /*public function getWeight(): ?int
    {
        if ($this->offsetExists('weight')) {
            return $this->offsetGet('weight');
        }

        return null;
    }*/
}

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
     */
    public function setName(string $value): self
    {
        $this->offsetSet('name', $value);

        return $this;
    }

    /**
     * Set the menu entry URL.
     */
    public function setUrl(string $value = null): self
    {
        $this->offsetSet('url', $value);

        return $this;
    }

    /**
     * Set menu entry weight.
     */
    public function setWeight(string $value): self
    {
        $this->offsetSet('weight', $value);

        return $this;
    }

    /**
     * Get menu entry weight.
     */
    public function getWeight(): ?int
    {
        return $this->offsetGet('weight');
    }
}

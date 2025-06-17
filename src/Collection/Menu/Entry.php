<?php

declare(strict_types=1);

/*
 * This file is part of Cecil.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Collection\Menu;

use Cecil\Collection\Item;

/**
 * Menu entry class.
 *
 * Represents a menu entry in a collection, providing methods to set and get the entry's name, URL, and weight.
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
     * Get menu entry name.
     */
    public function getName(): ?string
    {
        return $this->offsetGet('name');
    }

    /**
     * Set the menu entry URL.
     */
    public function setUrl(?string $value = null): self
    {
        $this->offsetSet('url', $value);

        return $this;
    }

    /**
     * Get menu entry URL.
     */
    public function getUrl(): ?string
    {
        return $this->offsetGet('url');
    }

    /**
     * Set menu entry weight.
     */
    public function setWeight(int $value): self
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

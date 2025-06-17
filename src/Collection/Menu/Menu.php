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

namespace Cecil\Collection\Menu;

use Cecil\Collection\Collection as CecilCollection;
use Cecil\Collection\CollectionInterface;
use Cecil\Collection\ItemInterface;

/**
 * Menu item class.
 *
 * Represents a menu in a collection, allowing for the addition and replacement of menu entries.
 */
class Menu extends CecilCollection implements ItemInterface
{
    /**
     * Add or replace menu entry.
     * {@inheritdoc}
     */
    public function add(ItemInterface $item): CollectionInterface
    {
        if ($this->has($item->getId())) {
            $this->replace($item->getId(), $item);

            return $this;
        }

        return parent::add($item);
    }
}

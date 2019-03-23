<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Collection\Menu;

use Cecil\Collection\Collection as CecilCollection;
use Cecil\Collection\CollectionInterface;
use Cecil\Collection\ItemInterface;

/**
 * Class Menu.
 */
class Menu extends CecilCollection implements ItemInterface
{
    /**
     * Add menu entry.
     * {@inheritdoc}
     */
    public function add(ItemInterface $item): CollectionInterface
    {
        //$this->items[$item->getId()] = $item;
        $this->items[] = $item;

        return $this;
    }
}

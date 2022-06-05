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
        if ($this->has($item->getId())) {
            $this->replace($item->getId(), $item);

            return $this;
        }

        return parent::add($item);
    }
}

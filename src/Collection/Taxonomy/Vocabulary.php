<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Collection\Taxonomy;

use Cecil\Collection\Collection as CecilCollection;
use Cecil\Collection\CollectionInterface;
use Cecil\Collection\ItemInterface;

/**
 * Class Vocabulary.
 */
class Vocabulary extends CecilCollection implements ItemInterface
{
    /**
     * Adds a term to a Vocabulary collection.
     * {@inheritdoc}
     */
    public function add(ItemInterface $item): CollectionInterface
    {
        if ($this->has($item->getId())) {
            // return if already exists
            return $this;
        }
        //$this->items[$item->getId()] = $item;
        $this->items[] = $item;

        return $this;
    }
}

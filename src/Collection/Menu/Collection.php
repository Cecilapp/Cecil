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
use Cecil\Collection\ItemInterface;

/**
 * Class Collection.
 */
class Collection extends CecilCollection
{
    /**
     * Return a Menu collection.
     * {@inheritdoc}
     */
    public function get(string $id): ItemInterface
    {
        return parent::get($id);
    }

    /**
     * Checks if menu exists.
     */
    public function __isset(string $id): bool
    {
        if ($this->has($id)) {
            return true;
        }

        return false;
    }
}

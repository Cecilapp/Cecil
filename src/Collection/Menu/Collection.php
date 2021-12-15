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
     * Checks if menu item exists.
     *
     * @param string $name
     */
    public function __isset(string $name)
    {
        if (!$this->has($name)) {
            throw new \Exception(\sprintf('Menu "%s" not found', $name));
        }
    }
}

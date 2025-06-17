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
use Cecil\Collection\ItemInterface;

/**
 * Menu collection class.
 *
 * Represents a collection of menus, providing methods to retrieve and check for existence of menus.
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

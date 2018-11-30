<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Collection\Menu;

use PHPoole\Collection\Collection as PHPooleCollection;

/**
 * Class Collection.
 */
class Collection extends PHPooleCollection
{
    /**
     * Return a Menu collection (creates it if not exists)
     * {@inheritdoc}
     */
    public function get($id)
    {
        if (!$this->has($id)) {
            $this->add(new Menu($id));
        }

        return $this->items[$id];
    }
}

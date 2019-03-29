<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Collection\Taxonomy;

use Cecil\Collection\Collection as CecilCollection;
use Cecil\Collection\ItemInterface;

/**
 * Class Collection.
 */
class Collection extends CecilCollection
{
    /**
     * Return a Vocabulary collection (creates it if not exists).
     * {@inheritdoc}
     */
    /*
    public function get(string $id): ItemInterface
    {
        if (!$this->has($id)) {
            $this->add(new Vocabulary($id));
        }

        return parent::get($id);
    }
    */
}

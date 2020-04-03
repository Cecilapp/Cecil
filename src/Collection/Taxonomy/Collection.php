<?php
/**
 * This file is part of the Cecil/Cecil package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
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
     * Returns a Vocabulary collection (creates it if not exists).
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

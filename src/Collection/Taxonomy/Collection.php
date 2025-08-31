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

namespace Cecil\Collection\Taxonomy;

use Cecil\Collection\Collection as CecilCollection;

/**
 * Taxonomy collection class.
 *
 * Represents a collection of vocabularies, providing methods to retrieve vocabularies by their ID.
 */
class Collection extends CecilCollection
{
    /**
     * {@inheritdoc}
     */
    public function get(string $id): Vocabulary
    {
        return parent::get($id);
    }
}

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

use Cecil\Collection\ItemInterface;
use Cecil\Collection\Page\Collection as CecilCollection;

/**
 * Term class.
 *
 * Represents a term in a taxonomy, extending the base collection class to include a name property.
 * Provides methods to set and get the term's name.
 */
class Term extends CecilCollection implements ItemInterface
{
    /** @var string Term's name. */
    protected $name;

    /**
     * Set term's name.
     */
    public function setName(string $value): self
    {
        $this->name = $value;

        return $this;
    }

    /**
     * Get term's name.
     */
    public function getName(): ?string
    {
        return $this->name;
    }
}

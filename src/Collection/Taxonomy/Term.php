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

use Cecil\Collection\Page\Collection as CecilCollection;
use Cecil\Collection\ItemInterface;

/**
 * Class Term.
 */
class Term extends CecilCollection implements ItemInterface
{
    /** @var string Term's name. */
    protected $name;

    /**
     * Set term's name.
     *
     * @param string $value
     *
     * @return self
     */
    public function setName(string $value): self
    {
        $this->name = $value;

        return $this;
    }

    /**
     * Get term's name.
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }
}

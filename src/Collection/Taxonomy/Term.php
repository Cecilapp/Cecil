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

    /**
     * Sorts terms by date.
     *
     * @return self
     */
    public function sortByDate(): self
    {
        return $this->usort(function ($a, $b) {
            /*if (!isset($a['date'])) {
                return -1;
            }
            if (!isset($b['date'])) {
                return 1;
            }*/
            if ($a['date'] == $b['date']) {
                return 0;
            }

            return ($a['date'] > $b['date']) ? -1 : 1;
        });
    }
}

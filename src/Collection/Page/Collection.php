<?php

/*
 * This file is part of Cecil.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Collection\Page;

use Cecil\Collection\Collection as CecilCollection;

/**
 * Class Collection.
 */
class Collection extends CecilCollection
{
    /**
     * Returns all "showable" pages.
     */
    public function showable(): self
    {
        $filteredPages = $this->filter(function (Page $page) {
            if ($page->getVariable('published') === true
                && $page->isVirtual() === false
                && $page->getVariable('redirect') === null
                && $page->getVariable('exclude') !== true) {
                return true;
            }
        });

        return $filteredPages;
    }

    /**
     * Alias of showable().
     */
    public function all(): self
    {
        return $this->showable();
    }

    /**
     * Sorts pages by date: the most recent first.
     */
    public function sortByDate(): self
    {
        return $this->usort(function ($a, $b) {
            if ($a['date'] == $b['date']) {
                return 0;
            }

            return ($a['date'] > $b['date']) ? -1 : 1;
        });
    }

    /**
     * Sorts pages by title (natural sort).
     */
    public function sortByTitle(): self
    {
        return $this->usort(function ($a, $b) {
            return strnatcmp($a['title'], $b['title']);
        });
    }

    /**
     * Sorts by weight (the heaviest first).
     */
    public function sortByWeight(): self
    {
        return $this->usort(function ($a, $b) {
            if ($a['weight'] == $b['weight']) {
                return 0;
            }

            return ($a['weight'] < $b['weight']) ? -1 : 1;
        });
    }
}

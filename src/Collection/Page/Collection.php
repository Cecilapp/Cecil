<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
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
     * Return all not virtual pages.
     */
    public function all(): self
    {
        $filteredPages = $this->filter(function (Page $page) {
            if ($page->isVirtual() === false) {
                return true;
            }
        });

        return $filteredPages;
    }

    /**
     * Sort items by date: the most recent first.
     *
     * @return self
     */
    public function sortByDate(): self
    {
        return $this->usort(function ($a, $b) {
            if (!isset($a['date'])) {
                return -1;
            }
            if (!isset($b['date'])) {
                return 1;
            }
            if ($a['date'] == $b['date']) {
                return 0;
            }

            return ($a['date'] > $b['date']) ? -1 : 1;
        });
    }

    /**
     * Sort items by title (natural sort).
     *
     * @return self
     */
    public function sortByTitle(): self
    {
        return $this->usort(function ($a, $b) {
            return strnatcmp($a['title'], $b['title']);
        });
    }

    /**
     * Sort by weight: the heaviest first.
     *
     * @return self
     */
    public function sortByWeight(): self
    {
        return $this->usort(function ($a, $b) {
            if (!isset($a['weight'])) {
                return 1;
            }
            if (!isset($b['weight'])) {
                return -1;
            }
            if ($a['weight'] == $b['weight']) {
                return 0;
            }

            return ($a['weight'] < $b['weight']) ? -1 : 1;
        });
    }
}

<?php

declare(strict_types=1);

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
        return $this->filter(function (Page $page) {
            if ($page->getVariable('published') === true
                && $page->isVirtual() === false
                && $page->getVariable('redirect') === null
                && $page->getVariable('exclude') !== true) {
                return true;
            }
        });
    }

    /**
     * Alias of showable().
     */
    public function all(): self
    {
        return $this->showable();
    }

    /**
     * Sorts pages by date (or 'updated' date): the most recent first.
     *
     * @param array|string $options
     */
    public function sortByDate($options = null): self
    {
        // backward compatibility
        if (is_string($options)) {
            $options['variable'] = $options;
        }
        // options
        $options['variable'] = $options['variable'] ?? 'date';
        $options['descTitle'] = $options['descTitle'] ?? false;
        $options['reverse'] = $options['reverse'] ?? false;

        $pages = $this->usort(function ($a, $b) use ($options) {
            if ($a[$options['variable']] == $b[$options['variable']]) {
                // if dates are equal and "descTitle" is true
                if ($options['descTitle'] && (isset($a['title']) && isset($b['title']))) {
                    return strnatcmp($b['title'], $a['title']);
                }

                return 0;
            }

            return $a[$options['variable']] > $b[$options['variable']] ? -1 : 1;
        });
        if ($options['reverse']) {
            $pages = $pages->reverse();
        }

        return $pages;
    }

    /**
     * Sorts pages by title (natural sort).
     */
    public function sortByTitle($options = null): self
    {
        // options
        if (!isset($options['reverse'])) {
            $options['reverse'] = false;
        }

        return $this->usort(function ($a, $b) use ($options) {
            return ($options['reverse'] ? -1 : 1) * strnatcmp($a['title'], $b['title']);
        });
    }

    /**
     * Sorts by weight (the heaviest first).
     */
    public function sortByWeight($options = null): self
    {
        // options
        if (!isset($options['reverse'])) {
            $options['reverse'] = false;
        }

        return $this->usort(function ($a, $b) use ($options) {
            if ($a['weight'] == $b['weight']) {
                return 0;
            }

            return ($options['reverse'] ? -1 : 1) * ($a['weight'] < $b['weight'] ? -1 : 1);
        });
    }
}

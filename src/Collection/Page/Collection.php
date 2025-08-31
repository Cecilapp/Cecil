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

namespace Cecil\Collection\Page;

use Cecil\Collection\Collection as CecilCollection;
use Cecil\Exception\RuntimeException;

/**
 * Pages collection class.
 *
 * Represents a collection of pages, providing methods to filter and sort them.
 */
class Collection extends CecilCollection
{
    /**
     * Returns all "showable" pages.
     */
    public function showable(): self
    {
        return $this->filter(function (Page $page) {
            if (
                $page->getVariable('published') === true      // page is published
                && (
                    $page->getVariable('excluded') !== true   // page is listed
                    && $page->getVariable('exclude') !== true // backward compatibility
                )
                && $page->isVirtual() === false               // page is created from a file
                && $page->getVariable('redirect') === null    // page is not a redirection
            ) {
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
     * Sorts pages by.
     *
     * $options:
     * [date|updated|title|weight]
     * or
     * [
     *   variable   => date|updated|title|weight
     *   desc_title => false|true
     *   reverse    => false|true
     * ]
     */
    public function sortBy(string|array|null $options): self
    {
        $sortBy = \is_string($options) ? $options : $options['variable'] ?? 'date';
        $sortMethod = \sprintf('sortBy%s', ucfirst(str_replace('updated', 'date', $sortBy)));
        if (!method_exists($this, $sortMethod)) {
            throw new RuntimeException(\sprintf('"%s" is not a valid value for `sortby` to sort collection "%s".', $sortBy, $this->getId()));
        }

        return $this->$sortMethod($options);
    }

    /**
     * Sorts pages by date (or 'updated'): the most recent first.
     */
    public function sortByDate(string|array|null $options = null): self
    {
        $opt = [];
        // backward compatibility (i.e. $options = 'updated')
        if (\is_string($options)) {
            $opt['variable'] = $options;
        }
        // options
        $opt['variable'] = $options['variable'] ?? 'date';
        $opt['descTitle'] = $options['descTitle'] ?? false;
        $opt['reverse'] = $options['reverse'] ?? false;
        // sort
        $pages = $this->usort(function ($a, $b) use ($opt) {
            if ($a[$opt['variable']] == $b[$opt['variable']]) {
                // if dates are equal and "descTitle" is true
                if ($opt['descTitle'] && (isset($a['title']) && isset($b['title']))) {
                    return strnatcmp($b['title'], $a['title']);
                }

                return 0;
            }

            return $a[$opt['variable']] > $b[$opt['variable']] ? -1 : 1;
        });
        if ($opt['reverse']) {
            $pages = $pages->reverse();
        }

        return $pages;
    }

    /**
     * Sorts pages by title (natural sort).
     */
    public function sortByTitle(string|array|null $options = null): self
    {
        $opt = [];
        // options
        $opt['reverse'] = $options['reverse'] ?? false;
        // sort
        return $this->usort(function ($a, $b) use ($opt) {
            return ($opt['reverse'] ? -1 : 1) * strnatcmp($a['title'], $b['title']);
        });
    }

    /**
     * Sorts by weight (the heaviest first).
     */
    public function sortByWeight(string|array|null $options = null): self
    {
        $opt = [];
        // options
        $opt['reverse'] = $options['reverse'] ?? false;
        // sort
        return $this->usort(function ($a, $b) use ($opt) {
            if ($a['weight'] == $b['weight']) {
                return 0;
            }

            return ($opt['reverse'] ? -1 : 1) * ($a['weight'] < $b['weight'] ? -1 : 1);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $id): Page
    {
        return parent::get($id);
    }

    /**
     * {@inheritdoc}
     */
    public function first(): ?Page
    {
        return parent::first();
    }

    /**
     * {@inheritdoc}
     */
    public function filter(\Closure $callback): self
    {
        return parent::filter($callback);
    }

    /**
     * {@inheritdoc}
     */
    public function usort(?\Closure $callback = null): self
    {
        return parent::usort($callback);
    }

    /**
     * {@inheritdoc}
     */
    public function reverse(): self
    {
        return parent::reverse();
    }
}

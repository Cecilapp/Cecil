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

namespace Cecil\Renderer\Extension;

use Cecil\Builder;
use Cecil\Collection\CollectionInterface;
use Cecil\Collection\Page\Collection as PagesCollection;
use Cecil\Collection\Page\Page;
use Cecil\Collection\Page\Type;
use Twig\Extension\AbstractExtension;

/**
 * Collection Twig extension.
 *
 * Provides filters for filtering and sorting page collections in Twig templates.
 */
class Collection extends AbstractExtension
{
    /** @var Builder */
    protected $builder;

    public function __construct(Builder $builder)
    {
        $this->builder = $builder;
    }

    public function getFilters(): array
    {
        return [
            new \Twig\TwigFilter('sort_by_title', [$this, 'sortByTitle']),
            new \Twig\TwigFilter('sort_by_date', [$this, 'sortByDate']),
            new \Twig\TwigFilter('sort_by_weight', [$this, 'sortByWeight']),
            new \Twig\TwigFilter('filter_by', [$this, 'filterBy']),
        ];
    }

    /**
     * Filters by Section.
     */
    public function filterBySection(PagesCollection $pages, string $section): CollectionInterface
    {
        return $this->filterBy($pages, 'section', $section);
    }

    /**
     * Filters a pages collection by variable's name/value.
     */
    public function filterBy(PagesCollection $pages, string $variable, string $value): CollectionInterface
    {
        $filteredPages = $pages->filter(function (Page $page) use ($variable, $value) {
            // is a dedicated getter exists?
            $method = 'get' . ucfirst($variable);
            if (method_exists($page, $method) && $page->$method() == $value) {
                return $page->getType() == Type::PAGE->value && !$page->isVirtual() && true;
            }
            // or a classic variable
            if ($page->getVariable($variable) == $value) {
                return $page->getType() == Type::PAGE->value && !$page->isVirtual() && true;
            }
        });

        return $filteredPages;
    }

    /**
     * Sorts a collection by title.
     */
    public function sortByTitle(\Traversable $collection): array
    {
        $sort = \SORT_ASC;

        $collection = iterator_to_array($collection);
        array_multisort(array_keys(/** @scrutinizer ignore-type */ $collection), $sort, \SORT_NATURAL | \SORT_FLAG_CASE, $collection);

        return $collection;
    }

    /**
     * Sorts a collection by weight.
     *
     * @param \Traversable|array $collection
     */
    public function sortByWeight($collection): array
    {
        $callback = function ($a, $b) {
            if (!isset($a['weight'])) {
                $a['weight'] = 0;
            }
            if (!isset($b['weight'])) {
                $b['weight'] = 0;
            }
            if ($a['weight'] == $b['weight']) {
                return 0;
            }

            return $a['weight'] < $b['weight'] ? -1 : 1;
        };

        if (!\is_array($collection)) {
            $collection = iterator_to_array($collection);
        }
        usort(/** @scrutinizer ignore-type */ $collection, $callback);

        return $collection;
    }

    /**
     * Sorts by creation date (or 'updated' date): the most recent first.
     */
    public function sortByDate(\Traversable $collection, string $variable = 'date', bool $descTitle = false): array
    {
        $callback = function ($a, $b) use ($variable, $descTitle) {
            if ($a[$variable] == $b[$variable]) {
                // if dates are equal and "descTitle" is true
                if ($descTitle && (isset($a['title']) && isset($b['title']))) {
                    return strnatcmp($b['title'], $a['title']);
                }

                return 0;
            }

            return $a[$variable] > $b[$variable] ? -1 : 1;
        };

        $collection = iterator_to_array($collection);
        usort(/** @scrutinizer ignore-type */ $collection, $callback);

        return $collection;
    }
}

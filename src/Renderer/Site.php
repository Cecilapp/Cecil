<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Renderer;

use Cecil\Builder;
use Cecil\Collection\Page\Page;

/**
 * Class Site.
 */
class Site implements \ArrayAccess
{
    /**
     * Builder object.
     *
     * @var Builder
     */
    protected $builder;
    /**
     * Current language.
     *
     * @var string
     */
    protected $language;

    /**
     * Site constructor.
     *
     * @param Builder     $builder
     * @param string|null $language
     */
    public function __construct(Builder $builder, string $language = null)
    {
        $this->builder = $builder;
        $this->language = $language;
    }

    /**
     * Implement ArrayAccess.
     *
     * @param mixed $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->builder->getConfig()->has($offset);
    }

    /**
     * Implement ArrayAccess.
     *
     * @param mixed $offset
     *
     * @return mixed|null
     */
    public function offsetGet($offset)
    {
        // special cases
        switch ($offset) {
            case 'taxonomies':
                return $this->builder->getTaxonomies();
            case 'language':
                return new Language($this->builder->getConfig(), $this->language);
            case 'data':
                return $this->builder->getData();
        }

        return $this->builder->getConfig()->get($offset, $this->language);
    }

    /**
     * Implement ArrayAccess.
     *
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
    }

    /**
     * Implement ArrayAccess.
     *
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
    }

    /**
     * All pages, filtered by published status.
     */
    public function getPages()
    {
        return $this->builder->getPages()->filter(function (Page $page) {
            return $page->getVariable('published');
        });
    }

    /**
     * Navigation menus.
     */
    public function getMenus()
    {
        return $this->builder->getMenus();
    }

    /**
     * Current time.
     */
    public function getTime()
    {
        return time();
    }
}

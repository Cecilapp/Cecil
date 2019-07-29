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
     * Site constructor.
     *
     * @param Builder $builder
     */
    public function __construct(Builder $builder)
    {
        $this->builder = $builder;
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
        return $this->builder->getConfig()->getAll()->has($offset);
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
        switch ($offset) {
            case 'taxonomies':
                return $this->builder->getTaxonomies();
            case 'language':
                return new Language($this->builder->getConfig());
        }

        return $this->builder->getConfig()->getAll()->get($offset);
    }

    /**
     * Implement ArrayAccess.
     *
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        return $this->builder->getConfig()->getAll()->set($offset, $value);
    }

    /**
     * Implement ArrayAccess.
     *
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        return $this->builder->getConfig()->getAll()->remove($offset);
    }

    /**
     * All pages, filtered by published status.
     */
    public function getPages()
    {
        return $this->builder->getPages()->filter(function (Page $page) {
            return true;

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

<?php
/**
 * This file is part of the Cecil/Cecil package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Renderer;

use Cecil\Builder;

/**
 * Class Site.
 */
class Site implements \ArrayAccess
{
    /** @var Builder Builder object. */
    protected $builder;
    /** @var string Current language. */
    protected $language;

    /**
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
        // special cases
        switch ($offset) {
            case 'home':
            case 'menus':
                return true;
        }

        return $this->builder->getConfig()->has($offset);
    }

    /**
     * Implements ArrayAccess.
     *
     * @param mixed $offset
     *
     * @return mixed|null
     */
    public function offsetGet($offset)
    {
        // special cases
        switch ($offset) {
            case 'menus':
                return $this->builder->getMenus($this->language == $this->builder->getConfig()->getLanguageDefault() ? null : $this->language);
            case 'taxonomies':
                return $this->builder->getTaxonomies();
            case 'language':
                return new Language($this->builder->getConfig(), $this->language);
            case 'data':
                return $this->builder->getData();
            case 'static':
                return $this->builder->getStatic();
        }
        if ($offset == 'home') {
            if (count($this->builder->getConfig()->getLanguages()) > 1
                && $this->language != $this->builder->getConfig()->getLanguageDefault) {
                return sprintf('index.%s', $this->language);
            }

            return 'index';
        }

        return $this->builder->getConfig()->get($offset, $this->language);
    }

    /**
     * Implements ArrayAccess.
     *
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
    }

    /**
     * Implements ArrayAccess.
     *
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
    }

    /**
     * Returns all pages, by language.
     *
     * @return \Cecil\Collection\Page\Collection
     */
    public function getPages(): \Cecil\Collection\Page\Collection
    {
        return $this->builder->getPages()->filter(function ($page) {
            if ($this->language != $this->builder->getConfig()->getLanguageDefault()) {
                return $page->getVariable('language') == $this->language;
            }

            return is_null($page->getVariable('language'));
        });
    }

    /**
     * Returns all pages, with translations.
     *
     * @return \Cecil\Collection\Page\Collection
     */
    public function getPagesIntl(): \Cecil\Collection\Page\Collection
    {
        return $this->builder->getPages();
    }

    /**
     * Return current time.
     *
     * @return int
     */
    public function getTime(): int
    {
        return time();
    }
}

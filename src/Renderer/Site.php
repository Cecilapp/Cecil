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
use Cecil\Collection\Page\Page;

/**
 * Class Site.
 */
class Site implements \ArrayAccess
{
    /** @var Builder Builder object. */
    protected $builder;

    /** @var \Cecil\Config */
    protected $config;

    /** @var string Current language. */
    protected $language;

    public function __construct(Builder $builder, string $language)
    {
        $this->builder = $builder;
        $this->config = $this->builder->getConfig();
        $this->language = $language;
    }

    /**
     * Implement ArrayAccess.
     */
    public function offsetExists($offset): bool
    {
        // special cases
        switch ($offset) {
            case 'home':
            case 'menus':
                return true;
        }

        return $this->config->has($offset);
    }

    /**
     * Implements ArrayAccess.
     */
    public function offsetGet($offset)
    {
        // special cases
        switch ($offset) {
            case 'menus':
                return $this->builder->getMenus($this->language);
            case 'taxonomies':
                return $this->builder->getTaxonomies();
            case 'language':
                return new Language($this->config, $this->language);
            case 'data':
                return $this->builder->getData();
            case 'static':
                return $this->builder->getStatic();
            case 'home':
                return $this->language !== $this->config->getLanguageDefault() ? sprintf('index.%s', $this->language) : 'index';
        }

        return $this->config->get($offset, $this->language);
    }

    /**
     * Implements ArrayAccess.
     */
    public function offsetSet($offset, $value): void
    {
    }

    /**
     * Implements ArrayAccess.
     */
    public function offsetUnset($offset): void
    {
    }

    /**
     * Returns all pages, in the current language.
     */
    public function getPages(): \Cecil\Collection\Page\Collection
    {
        return $this->builder->getPages()->filter(function (Page $page) {
            // We should fix case of virtual pages without language
            if ($page->getVariable('language') === null && $this->language === $this->config->getLanguageDefault()) {
                return true;
            }

            return $page->getVariable('language') == $this->language;
        });
    }

    /**
     * Returns all pages, regardless of their translation.
     */
    public function getAllPages(): \Cecil\Collection\Page\Collection
    {
        return $this->builder->getPages();
    }

    /**
     * Alias of getAllPages().
     */
    public function getPagesIntl(): \Cecil\Collection\Page\Collection
    {
        return $this->getAllPages();
    }

    /**
     * Return current time.
     */
    public function getTime(): int
    {
        return time();
    }
}

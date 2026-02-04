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

namespace Cecil\Renderer;

use Cecil\Builder;
use Cecil\Collection\Page\Page as CollectionPage;

/**
 * Site renderer class.
 *
 * This class implements the \ArrayAccess interface to allow access to site
 * properties using array syntax. It provides access to various site-related
 * properties such as pages, menus, taxonomies, and language settings.
 * It also provides methods to retrieve specific pages and collections of pages
 * based on the current language or all pages regardless of their translation.
 */
class Site implements \ArrayAccess
{
    /**
     * Builder object.
     * @var Builder
     */
    protected $builder;
    /**
     * Configuration object.
     * @var \Cecil\Config
     */
    protected $config;
    /**
     * Current language code.
     * @var string
     */
    protected $language;

    public function __construct(Builder $builder, string $language)
    {
        $this->builder = $builder;
        $this->config = $this->builder->getConfig();
        $this->language = $language;
    }

    /**
     * Implement ArrayAccess.
     *
     * @param mixed $offset
     *
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset): bool
    {
        // special cases
        switch ($offset) {
            case 'menus':
            case 'taxonomies':
            case 'home':
            case 'debug':
            case 'build':
                return true;
        }

        return $this->config->has($offset);
    }

    /**
     * Implements \ArrayAccess.
     *
     * @param mixed $offset
     *
     * @return mixed|null
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        // If it's a built-in variable: dot not fetch data from config raw
        switch ($offset) {
            case 'pages':
                return $this->getPages();
            case 'menus':
                return $this->builder->getMenus($this->language);
            case 'taxonomies':
                return $this->builder->getTaxonomies($this->language);
            case 'data':
                return $this->builder->getData($this->language);
            case 'static':
                return $this->builder->getStatic();
            case 'language':
                return new Language($this->config, $this->language);
            case 'home':
                return 'index';
            case 'debug':
                return $this->builder->isDebug();
            case 'build':
                return Builder::getBuildId();
        }

        return $this->config->get($offset, $this->language);
    }

    /**
     * Implements \ArrayAccess.
     *
     * @param mixed $offset
     * @param mixed $value
     *
     * @SuppressWarnings(UnusedFormalParameter)
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value): void
    {
    }

    /**
     * Implements \ArrayAccess.
     *
     * @param mixed $offset
     *
     * @SuppressWarnings(UnusedFormalParameter)
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset): void
    {
    }

    /**
     * Returns a page for the provided language or the current one provided.
     *
     * @throws \DomainException
     */
    public function getPage(string $id, ?string $language = null): ?CollectionPage
    {
        $pageId = $id;
        $language = $language ?? $this->language;

        if ($language !== null && $language != $this->config->getLanguageDefault()) {
            $pageId = "$language/$id";
        }

        if ($this->builder->getPages()->has($pageId) === false) {
            // if multilingual == false
            if ($this->builder->getPages()->has($id) && $this->builder->getPages()->get($id)->getVariable('multilingual') === false) {
                return $this->builder->getPages()->get($id);
            }

            return null;
        }

        return $this->builder->getPages()->get($pageId);
    }

    /**
     * Returns all pages, in the current language.
     */
    public function getPages(): \Cecil\Collection\Page\Collection
    {
        return $this->builder->getPages()->filter(function (CollectionPage $page) {
            // We should fix case of virtual pages without language
            if (
                $page->getVariable('language') === null
                && $this->language == $this->config->getLanguageDefault()
            ) {
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
     * Returns current time.
     */
    public function getTime(): int
    {
        return time();
    }

    /**
     * Returns the property value(s) of an output format.
     */
    public function getOutputProperty(string $name, string $property): string|array|null
    {
        return $this->config->getOutputFormatProperty($name, $property);
    }
}

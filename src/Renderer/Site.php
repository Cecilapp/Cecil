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
use Cecil\Config;

/**
 * Class Site.
 */
class Site
{
    /**
     * Builder object.
     *
     * @var Builder
     */
    protected $builder;
    /**
     * Configuration object.
     *
     * @var Config
     */
    protected $config;

    /**
     * Site constructor.
     *
     * @param Config $config
     */
    public function __construct(Builder $builder)
    {
        $this->builder = $builder;
        $this->config = $builder->getConfig();
    }

    public function __call($method, $args)
    {
        $camelMethod = 'get'.ucwords($method);
        // local method
        if (method_exists($this, $camelMethod)) {
            return $this->$camelMethod($args);
        }
        // Config method
        if (method_exists($this->config, $camelMethod)) {
            return $this->config->$camelMethod($args);
        }
        // Config Data
        return $this->config->get($method);
    }

    public function getPages()
    {
        return $this->builder->getPages()->filter(function (Page $page) {
            return $page->getVariable('published');
        });
    }

    public function getMenus()
    {
        return $this->builder->getMenus();
    }

    public function getTaxonomies()
    {
        return $this->builder->getTaxonomies();
    }

    public function getTime()
    {
        return time();
    }
}

<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Renderer;

use Cecil\Config;

/**
 * Class Language.
 */
class Language
{
    /**
     * Config.
     *
     * @var Config
     */
    protected $config;

    /**
     * Language constructor.
     *
     * @param Config
     */
    public function __construct($config)
    {
        $this->config = $config;
    }

    public function __toString()
    {
        return $this->config->getLanguageDefaultKey();
    }

    public function getName()
    {
        return $this->config->getLanguageProperty('name');
    }

    public function getLocale()
    {
        return $this->config->getLanguageProperty('locale');
    }

    public function getWeight()
    {
        return array_search($this->config->getLanguageDefaultKey(), array_keys($this->config->get('languages')));
    }
}

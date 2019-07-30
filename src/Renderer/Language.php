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
     * Current language.
     *
     * @var string
     */
    protected $language;

    /**
     * Language constructor.
     *
     * @param Config      $config
     * @param string|null $language
     */
    public function __construct(Config $config, string $language = null)
    {
        $this->config = $config;
        $this->language = $language;

        $locale = $this->config->getLanguageProperty('locale', $this->language);
        // The PHP Intl extension is needed to use localized date
        if (extension_loaded('intl')) {
            \Locale::setDefault($locale);
        }
        // The PHP Gettext extension is needed to use translation
        if (extension_loaded('gettext')) {
            $localePath = realpath($config->getSourceDir().'/locale');
            $domain = 'messages';
            putenv("LC_ALL=$locale");
            putenv("LANGUAGE=$locale");
            setlocale(LC_ALL, "$locale.UTF-8");
            bindtextdomain($domain, $localePath);
        }
    }

    public function __toString()
    {
        if ($this->language) {
            return $this->language;
        }

        return $this->config->getLanguageDefaultKey();
    }

    public function getName()
    {
        return $this->config->getLanguageProperty('name', $this->language);
    }

    public function getLocale()
    {
        return $this->config->getLanguageProperty('locale', $this->language);
    }

    public function getWeight()
    {
        return array_search((string) $this, array_keys($this->config->get('languages')));
    }
}

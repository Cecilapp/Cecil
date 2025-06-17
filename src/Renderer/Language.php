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

use Cecil\Exception\RuntimeException;

/**
 * Language class.
 *
 * This class is responsible for managing language properties such as name, locale, and weight.
 * It retrieves these properties from the configuration object and provides methods to access them.
 * It also ensures that the properties exist and are not empty, throwing an exception if they are.
 */
class Language
{
    /** @var \Cecil\Config */
    protected $config;

    /** @var string Current language. */
    protected $language;

    public function __construct(\Cecil\Config $config, ?string $language = null)
    {
        $this->config = $config;
        $this->language = $language;
        if ($language === null) {
            $this->language = $this->config->getLanguageDefault();
        }
    }

    /**
     * Returns the current language.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->language;
    }

    /**
     * Returns the name of the current or of the given language code.
     */
    public function getName(?string $language = null): ?string
    {
        if ($language !== null) {
            $this->language = $language;
        }
        if ($this->hasProperty('name')) {
            return $this->config->getLanguageProperty('name', $this->language);
        }

        return null;
    }

    /**
     * Returns the locale of the current or of the given language code.
     */
    public function getLocale(?string $language = null): ?string
    {
        if ($language !== null) {
            $this->language = $language;
        }
        if ($this->hasProperty('locale')) {
            return $this->config->getLanguageProperty('locale', $this->language);
        }

        return null;
    }

    /**
     * Returns the weight of the current or of the given language code.
     */
    public function getWeight(?string $language = null): int
    {
        if ($language !== null) {
            $this->language = $language;
        }

        return $this->config->getLanguageIndex($this->language);
    }

    /**
     * Checks if the given property exists for the current language.
     */
    private function hasProperty(string $property): bool
    {
        $value = $this->config->getLanguageProperty($property, $this->language);

        if (empty($value)) {
            $language = $this->language ?: $this->config->getLanguageDefault();

            throw new RuntimeException(\sprintf('The property "%s" is empty for language "%s".', $property, $language));
        }

        return true;
    }
}

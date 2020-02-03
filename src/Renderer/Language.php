<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Renderer;

use Cecil\Config;
use Cecil\Exception\Exception;

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
    }

    public function __toString()
    {
        if ($this->language) {
            return $this->language;
        }

        return $this->config->getLanguageDefault();
    }

    public function getName(): ?string
    {
        if ($this->checkProperty('name')) {
            return $this->config->getLanguageProperty('name', $this->language);
        }
    }

    public function getLocale(): ?string
    {
        if ($this->checkProperty('locale')) {
            return $this->config->getLanguageProperty('locale', $this->language);
        }
    }

    public function getWeight(): int
    {
        if ($this->language) {
            return $this->config->getLanguageIndex($this->language);
        }

        return 0;
    }

    private function checkProperty(string $property): bool
    {
        $value = $this->config->getLanguageProperty($property, $this->language);

        if (empty($value)) {
            $language = $this->language ?: $this->config->getLanguageDefault();

            throw new Exception(sprintf(
                'The property "%s" is empty for language "%s".',
                $property,
                $language
            ));
        }

        return true;
    }
}

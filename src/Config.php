<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil;

use Cecil\Exception\Exception;
use Dflydev\DotAccessData\Data;

/**
 * Class Config.
 */
class Config implements \ArrayAccess
{
    /**
     * Configuration is a Data object.
     *
     * @var Data
     */
    protected $data;
    /**
     * Source directory.
     *
     * @var string
     */
    protected $sourceDir;
    /**
     * Destination directory.
     *
     * @var string
     */
    protected $destinationDir;

    /**
     * Config constructor.
     *
     * @param Config|array|null $config
     */
    public function __construct($config = null)
    {
        $data = new Data(include __DIR__.'/../config/default.php');

        if ($config) {
            if ($config instanceof self) {
                $data->importData($config->getAll());
            } elseif (is_array($config)) {
                $data->import($config);
            }
        }

        /**
         * Apply environment variables.
         */
        $applyEnv = function ($array) use ($data) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveArrayIterator($array),
                \RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($iterator as $leafValue) {
                $path = [];
                foreach (range(0, $iterator->getDepth()) as $depth) {
                    $path[] = $iterator->getSubIterator($depth)->key();
                }
                $sPath = implode('_', $path);
                if ($getEnv = getenv('CECIL_'.strtoupper($sPath))) {
                    $data->set(str_replace('_', '.', strtolower($sPath)), $getEnv);
                }
            }
        };
        $applyEnv($data->export());

        $this->setFromData($data);
    }

    /**
     * Import an array to the current configuration.
     *
     * @param array $config
     */
    public function import($config)
    {
        if (is_array($config)) {
            $data = $this->getAll();
            $origin = $data->export();
            $data->import($config);
            $data->import($origin);
            $this->setFromData($data);
        }
    }

    /**
     * Set a Data object as configuration.
     *
     * @param Data $data
     *
     * @return $this
     */
    protected function setFromData(Data $data)
    {
        if ($this->data !== $data) {
            $this->data = $data;
        }

        return $this;
    }

    /**
     * Get configuration as a Data object.
     *
     * @return Data
     */
    public function getAll()
    {
        return $this->data;
    }

    /**
     * Get configuration as an array.
     *
     * @return array
     */
    public function getAllAsArray()
    {
        return $this->data->export();
    }

    /**
     * Get the value of a configuration's key'.
     *
     * @param string $key
     * @param string $default
     *
     * @return array|mixed|null
     */
    public function get($key, $default = '')
    {
        return $this->data->get($key, $default);
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
        return $this->data->has("site.$offset");
    }

    /**
     * Implement ArrayAccess.
     *
     * @param mixed $offset
     *
     * @return null
     */
    public function offsetGet($offset)
    {
        return $this->data->get("site.$offset");
    }

    /**
     * Implement ArrayAccess.
     *
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        return $this->data->set("site.$offset", $value);
    }

    /**
     * Implement ArrayAccess.
     *
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        return $this->data->remove("site.$offset");
    }

    /**
     * Set the source directory.
     *
     * @param null $sourceDir
     *
     * @throws Exception
     *
     * @return $this
     */
    public function setSourceDir($sourceDir = null)
    {
        if ($sourceDir === null) {
            $sourceDir = getcwd();
        }
        if (!is_dir($sourceDir)) {
            throw new \InvalidArgumentException(sprintf('The directory "%s" is not a valid source!', $sourceDir));
        }
        $this->sourceDir = $sourceDir;

        return $this;
    }

    /**
     * Get the source directory.
     *
     * @return string
     */
    public function getSourceDir()
    {
        return $this->sourceDir;
    }

    /**
     * Set the destination directory.
     *
     * @param null $destinationDir
     *
     * @throws Exception
     *
     * @return $this
     */
    public function setDestinationDir($destinationDir = null)
    {
        if ($destinationDir === null) {
            $destinationDir = $this->sourceDir;
        }
        if (!is_dir($destinationDir)) {
            throw new \InvalidArgumentException(sprintf('The directory "%s" is not a valid destination!', $destinationDir));
        }
        $this->destinationDir = $destinationDir;

        return $this;
    }

    /**
     * Get the destination directory.
     *
     * @return string
     */
    public function getDestinationDir()
    {
        return $this->destinationDir;
    }

    /**
     * Paths helpers.
     */

    /**
     * Return the path of the content directory.
     *
     * @return string
     */
    public function getContentPath()
    {
        return $this->getSourceDir().'/'.$this->get('content.dir');
    }

    /**
     * Return the path of templates directory.
     *
     * @return string
     */
    public function getLayoutsPath()
    {
        return $this->getSourceDir().'/'.$this->get('layouts.dir');
    }

    /**
     * Return the path of themes directory.
     *
     * @return string
     */
    public function getThemesPath()
    {
        return $this->getSourceDir().'/'.$this->get('themes.dir');
    }

    /**
     * Return the path of internal templates directory.
     *
     * @return string
     */
    public function getInternalLayoutsPath()
    {
        return __DIR__.'/../'.$this->get('layouts.internal.dir');
    }

    /**
     * Return the path of the output directory.
     *
     * @return string
     */
    public function getOutputPath()
    {
        return $this->getDestinationDir().'/'.$this->get('site.output.dir');
    }

    /**
     * Return the path of static files directory.
     *
     * @return string
     */
    public function getStaticPath()
    {
        return $this->getSourceDir().'/'.$this->get('static.dir');
    }

    /**
     * Return a "clean" array of an output format.
     *
     * @param string $format
     *
     * @return array
     */
    public function getOutputFormat(string $format): array
    {
        $default = [
            'mediatype' => null, // 'text/html'
            'subpath'   => null, // ''
            'suffix'    => null, // '/index'
            'extension' => null, // 'html'
        ];

        $result = $this->get(sprintf('site.output.formats.%s', $format));

        return array_merge($default, $result);
    }

    /**
     * Theme helpers.
     */

    /**
     * Return theme(s) as an array.
     *
     * @return array|null
     */
    public function getTheme()
    {
        if ($themes = $this->get('theme')) {
            if (is_array($themes)) {
                return $themes;
            }

            return [$themes];
        }
    }

    /**
     * Has a (valid) theme(s)?
     *
     * @throws Exception
     *
     * @return bool
     */
    public function hasTheme()
    {
        if ($themes = $this->getTheme()) {
            foreach ($themes as $theme) {
                if (!Util::getFS()->exists($this->getThemeDirPath($theme, 'layouts'))) {
                    throw new Exception(sprintf(
                        "Theme directory '%s/%s/layouts' not found!",
                        $this->getThemesPath(),
                        $theme
                    ));
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Return the path of a specific theme's directory.
     * ("layouts" by default).
     *
     * @param string $theme
     * @param string $dir
     *
     * @return string
     */
    public function getThemeDirPath($theme, $dir = 'layouts')
    {
        return $this->getThemesPath().'/'.$theme.'/'.$dir;
    }

    /**
     * Language helpers.
     */

    /**
     * Return an array of available languages.
     *
     * @return array
     */
    public function getLanguages(): array
    {
        return $this->get('site.languages');
    }

    /**
     * Return the default language key (ie: "en", "fr-fr", etc.).
     *
     * @return string
     */
    public function getLanguageDefaultKey(): string
    {
        if ($this->get('site.language')) {
            return $this->get('site.language');
        }

        $languages = $this->getLanguages();
        if (!is_array($languages)) {
            throw new Exception('There is no default "language" in configuration!');
        }
        reset($languages);

        return key($languages);
    }

    /**
     * Return properties of a (specified or default) language.
     *
     * @return array
     */
    public function getLanguageProperties($key = null): array
    {
        $key = $key ?? $this->getLanguageDefaultKey();

        $languageProperties = $this->get(sprintf('site.languages.%s', $key));
        if (!is_array($languageProperties)) {
            throw new Exception(sprintf('Language "%s" is not correctly set in config!', $key));
        }

        return $languageProperties;
    }

    /**
     * Return the property value of a (specified or default) language.
     *
     * @return string
     */
    public function getLanguageProperty($property, $key = null): string
    {
        $properties = ['name', 'locale'];
        $languageProperties = $this->getLanguageProperties($key);

        if (!in_array($property, $properties)) {
            throw new Exception(sprintf(
                'Property language "%s" is not available!',
                $property
            ));
        }
        if (!\array_key_exists($property, $languageProperties)) {
            throw new Exception(sprintf(
                'Property "%s" is not defined for language "%s"!',
                $property,
                $languageProperties['name']
            ));
        }

        return $languageProperties[$property];
    }

    /**
     * Get the language key.
     */
    public function getLang(): string
    {
        return $this->getLanguageDefaultKey();
    }

    /**
     * Get the language name.
     */
    public function getLanguage(): string
    {
        return $this->getLanguageProperty('name');
    }

    /**
     * Get the language locale.
     */
    public function getLocale(): string
    {
        return $this->getLanguageProperty('locale');
    }
}

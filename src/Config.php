<?php
/**
 * This file is part of the Cecil/Cecil package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil;

use Cecil\Exception\Exception;
use Cecil\Util\Plateform;
use Dflydev\DotAccessData\Data;

/**
 * Class Config.
 */
class Config
{
    /** @var Data Configuration is a Data object. */
    protected $data;
    /** @var array Configuration. */
    protected $siteConfig;
    /** @var string Source directory. */
    protected $sourceDir;
    /** @var string Destination directory. */
    protected $destinationDir;

    /**
     * @param array|null $config
     */
    public function __construct(array $config = null)
    {
        // load default configuration
        $defaultConfig = realpath(Util::joinFile(__DIR__, '..', 'config/default.php'));
        if (Plateform::isPhar()) {
            $defaultConfig = Util::joinPath(Plateform::getPharPath(), 'config/default.php');
        }
        $this->data = new Data(include $defaultConfig);

        // import site config
        $this->siteConfig = $config;
        $this->importSiteConfig();
    }

    /**
     * Import site configuration.
     */
    protected function importSiteConfig(): void
    {
        $this->data->import($this->siteConfig);

        /**
         * Overrides configuration with environment variables.
         */
        $data = $this->getData();
        $applyEnv = function ($array) use ($data) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveArrayIterator($array),
                \RecursiveIteratorIterator::SELF_FIRST
            );
            $iterator->rewind();
            while ($iterator->valid()) {
                $path = [];
                foreach (range(0, $iterator->getDepth()) as $depth) {
                    $path[] = $iterator->getSubIterator($depth)->key();
                }
                $sPath = implode('_', $path);
                if ($getEnv = getenv('CECIL_'.strtoupper($sPath))) {
                    $data->set(str_replace('_', '.', strtolower($sPath)), $getEnv);
                }
                $iterator->next();
            }
        };
        $applyEnv($data->export());
    }

    /**
     * Import (theme) configuration.
     *
     * @param array|null $config
     *
     * @return void
     */
    public function import(array $config): void
    {
        $this->data->import($config);

        // re-import site config
        $this->importSiteConfig();
    }

    /**
     * Set a Data object as configuration.
     *
     * @param Data $data
     *
     * @return self
     */
    protected function setData(Data $data): self
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
    public function getData(): Data
    {
        return $this->data;
    }

    /**
     * Get configuration as an array.
     *
     * @return array
     */
    public function getAsArray(): array
    {
        return $this->data->export();
    }

    /**
     * Is configuration's key' exists?
     *
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key): bool
    {
        return $this->data->has($key);
    }

    /**
     * Get the value of a configuration's key'.
     *
     * @param string      $key
     * @param string|null $language
     *
     * @return mixed|null
     */
    public function get(string $key, string $language = null)
    {
        if ($language !== null) {
            $index = $this->getLanguageIndex($language);
            $keyLang = sprintf('languages.%s.config.%s', $index, $key);
            if ($this->data->has($keyLang)) {
                return $this->data->get($keyLang);
            }
        }

        return $this->data->get($key);
    }

    /**
     * Set the source directory.
     *
     * @param string|null $sourceDir
     *
     * @throws \InvalidArgumentException
     *
     * @return self
     */
    public function setSourceDir(string $sourceDir = null): self
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
    public function getSourceDir(): string
    {
        return $this->sourceDir;
    }

    /**
     * Set the destination directory.
     *
     * @param string|null $destinationDir
     *
     * @throws \InvalidArgumentException
     *
     * @return self
     */
    public function setDestinationDir(string $destinationDir = null): self
    {
        if ($destinationDir === null) {
            $destinationDir = $this->sourceDir;
        }
        if (!is_dir($destinationDir)) {
            throw new \InvalidArgumentException(sprintf(
                'The directory "%s" is not a valid destination!',
                $destinationDir
            ));
        }
        $this->destinationDir = $destinationDir;

        return $this;
    }

    /**
     * Get the destination directory.
     *
     * @return string
     */
    public function getDestinationDir(): string
    {
        return $this->destinationDir;
    }

    /**
     * Path helpers.
     */

    /**
     * Return the path of the content directory.
     *
     * @return string
     */
    public function getContentPath(): string
    {
        return Util::joinFile($this->getSourceDir(), (string) $this->get('content.dir'));
    }

    /**
     * Return the path of the data directory.
     *
     * @return string
     */
    public function getDataPath(): string
    {
        return Util::joinFile($this->getSourceDir(), (string) $this->get('data.dir'));
    }

    /**
     * Return the path of templates directory.
     *
     * @return string
     */
    public function getLayoutsPath(): string
    {
        return Util::joinFile($this->getSourceDir(), (string) $this->get('layouts.dir'));
    }

    /**
     * Return the path of themes directory.
     *
     * @return string
     */
    public function getThemesPath(): string
    {
        return Util::joinFile($this->getSourceDir(), (string) $this->get('themes.dir'));
    }

    /**
     * Return the path of internal templates directory.
     *
     * @return string
     */
    public function getInternalLayoutsPath(): string
    {
        return Util::joinPath(__DIR__, '..', (string) $this->get('layouts.internal.dir'));
    }

    /**
     * Return the path of the output directory.
     *
     * @return string
     */
    public function getOutputPath(): string
    {
        return Util::joinFile($this->getDestinationDir(), (string) $this->get('output.dir'));
    }

    /**
     * Return the path of static files directory.
     *
     * @return string
     */
    public function getStaticPath(): string
    {
        return Util::joinFile($this->getSourceDir(), (string) $this->get('static.dir'));
    }

    /**
     * Is cache dir is absolute to system files
     * or relative to project destination?
     *
     * @return bool
     */
    public function isCacheDirIsAbsolute(): bool
    {
        $path = (string) $this->get('cache.dir');
        if (Util::joinFile($path) == realpath(Util::joinFile($path))) {
            return true;
        }

        return false;
    }

    /**
     * Return cache path.
     *
     * @return string
     */
    public function getCachePath(): string
    {
        if ($this->isCacheDirIsAbsolute()) {
            $cacheDir = Util::joinFile((string) $this->get('cache.dir'));
            $cacheDir = Util::joinFile($cacheDir, 'cecil');
            Util::getFS()->mkdir($cacheDir);

            return $cacheDir;
        }

        return Util::joinFile($this->getDestinationDir(), (string) $this->get('cache.dir'));
    }

    /**
     * Return the property value of an output format.
     *
     * @param string $name
     * @param string $property
     *
     * @return string|array|null
     */
    public function getOutputFormatProperty(string $name, string $property)
    {
        $properties = array_column((array) $this->get('output.formats'), $property, 'name');

        if (empty($properties)) {
            throw new Exception(sprintf(
                'Property "%s" is not defined for format "%s".',
                $property,
                $name
            ));
        }

        if (!array_key_exists($name, $properties)) {
            return null;
        }

        return $properties[$name];
    }

    /**
     * Theme helpers.
     */

    /**
     * Return theme(s) as an array.
     *
     * @return array|null
     */
    public function getTheme(): ?array
    {
        if ($themes = $this->get('theme')) {
            if (is_array($themes)) {
                return $themes;
            }

            return [$themes];
        }

        return null;
    }

    /**
     * Has a (valid) theme(s)?
     *
     * @throws Exception
     *
     * @return bool
     */
    public function hasTheme(): bool
    {
        if ($themes = $this->getTheme()) {
            foreach ($themes as $theme) {
                if (!Util::getFS()->exists($this->getThemeDirPath($theme, 'layouts'))) {
                    throw new Exception(sprintf(
                        'Theme directory "%s/%s/layouts" not found!',
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
    public function getThemeDirPath(string $theme, string $dir = 'layouts'): string
    {
        return Util::joinFile($this->getThemesPath(), $theme, $dir);
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
        return $this->get('languages');
    }

    /**
     * Return the default language code (ie: "en", "fr-fr", etc.).
     *
     * @return string
     */
    public function getLanguageDefault(): string
    {
        if (!$this->get('language')) {
            throw new Exception('There is no default "language" in configuration.');
        }

        return $this->get('language');
    }

    /**
     * Return a language code index.
     *
     * @param string $code
     *
     * @return int
     */
    public function getLanguageIndex(string $code): int
    {
        $array = array_column($this->getLanguages(), 'code');

        if (!$index = array_search($code, $array)) {
            throw new Exception(sprintf('The language code "%s" is not defined.', $code));
        }

        return $index;
    }

    /**
     * Return the property value of a (specified or default) language.
     *
     * @param string      $property
     * @param string|null $code
     *
     * @return string|null
     */
    public function getLanguageProperty(string $property, string $code = null): ?string
    {
        $code = $code ?? $this->getLanguageDefault();

        $properties = array_column($this->getLanguages(), $property, 'code');

        if (empty($properties)) {
            throw new Exception(sprintf(
                'Property "%s" is not defined for language "%s".',
                $property,
                $code
            ));
        }

        return $properties[$code];
    }
}

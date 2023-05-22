<?php

declare(strict_types=1);

/*
 * This file is part of Cecil.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil;

use Cecil\Exception\RuntimeException;
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

    /** @var array Languages. */
    protected $languages = null;

    public const LANG_CODE_PATTERN = '([a-z]{2}(-[A-Z]{2})?)'; // "fr" or "fr-FR"
    public const LANG_LOCALE_PATTERN = '[a-z]{2}(_[A-Z]{2})?(_[A-Z]{2})?'; // "fr" or "fr_FR" or "no_NO_NY"

    /**
     * Build the Config object with the default config + the optional given array.
     */
    public function __construct(?array $config = null)
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
     * Imports site configuration.
     */
    private function importSiteConfig(): void
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
                if ($getEnv = getenv('CECIL_' . strtoupper($sPath))) {
                    $data->set(str_replace('_', '.', strtolower($sPath)), $this->castSetValue($getEnv));
                }
                $iterator->next();
            }
        };
        $applyEnv($data->export());
    }

    /**
     * Casts boolean value given to set() as string.
     *
     * @param mixed $value
     *
     * @return bool|mixed
     */
    private function castSetValue($value)
    {
        if (\is_string($value)) {
            switch ($value) {
                case 'true':
                    return true;
                case 'false':
                    return false;
                default:
                    return $value;
            }
        }

        return $value;
    }

    /**
     * Imports (theme) configuration.
     */
    public function import(array $config): void
    {
        $this->data->import($config);

        // re-import site config
        $this->importSiteConfig();
    }

    /**
     * Set a Data object as configuration.
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
     */
    protected function getData(): Data
    {
        return $this->data;
    }

    /**
     * Get configuration as an array.
     */
    public function getAsArray(): array
    {
        return $this->data->export();
    }

    /**
     * Is configuration's key exists?
     */
    public function has(string $key): bool
    {
        return $this->data->has($key);
    }

    /**
     * Get the value of a configuration's key.
     *
     * @param string $key      Configuration key
     * @param string $language Language code (optionnal)
     * @param bool   $fallback Set to false to not return the value in the default language as fallback
     *
     * @return mixed|null
     */
    public function get(string $key, ?string $language = null, bool $fallback = true)
    {
        if ($language !== null) {
            $langIndex = $this->getLanguageIndex($language);
            $keyLang = "languages.$langIndex.config.$key";
            if ($this->data->has($keyLang)) {
                return $this->data->get($keyLang);
            }
            if ($language !== $this->getLanguageDefault() && $fallback === false) {
                return null;
            }
        }
        if ($this->data->has($key)) {
            return $this->data->get($key);
        }

        return null;
    }

    /**
     * Set the source directory.
     *
     * @throws \InvalidArgumentException
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
     */
    public function getSourceDir(): string
    {
        return $this->sourceDir;
    }

    /**
     * Set the destination directory.
     *
     * @throws \InvalidArgumentException
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
     */
    public function getDestinationDir(): string
    {
        return $this->destinationDir;
    }

    /**
     * Path helpers.
     */

    /**
     * Returns the path of the pages directory.
     */
    public function getPagesPath(): string
    {
        $path = Util::joinFile($this->getSourceDir(), (string) $this->get('pages.dir'));

        // legacy support
        if (!is_dir($path)) {
            $path = Util::joinFile($this->getSourceDir(), 'content');
        }

        return $path;
    }

    /**
     * Returns the path of the data directory.
     */
    public function getDataPath(): string
    {
        return Util::joinFile($this->getSourceDir(), (string) $this->get('data.dir'));
    }

    /**
     * Returns the path of templates directory.
     */
    public function getLayoutsPath(): string
    {
        return Util::joinFile($this->getSourceDir(), (string) $this->get('layouts.dir'));
    }

    /**
     * Returns the path of themes directory.
     */
    public function getThemesPath(): string
    {
        return Util::joinFile($this->getSourceDir(), (string) $this->get('themes.dir'));
    }

    /**
     * Returns the path of internal templates directory.
     */
    public function getInternalLayoutsPath(): string
    {
        return Util::joinPath(__DIR__, '..', (string) $this->get('layouts.internal.dir'));
    }

    /**
     * Returns the path of translations directory.
     */
    public function getTranslationsPath(): string
    {
        return Util::joinFile($this->getSourceDir(), (string) $this->get('translations.dir'));
    }

    /**
     * Returns the path of internal translations directory.
     */
    public function getInternalTranslationsPath(): string
    {
        if (Util\Plateform::isPhar()) {
            return Util::joinPath(Plateform::getPharPath(), (string) $this->get('translations.internal.dir'));
        }

        return realpath(Util::joinPath(__DIR__, '..', (string) $this->get('translations.internal.dir')));
    }

    /**
     * Returns the path of the output directory.
     */
    public function getOutputPath(): string
    {
        return Util::joinFile($this->getDestinationDir(), (string) $this->get('output.dir'));
    }

    /**
     * Returns the path of static files directory.
     */
    public function getStaticPath(): string
    {
        return Util::joinFile($this->getSourceDir(), (string) $this->get('static.dir'));
    }

    /**
     * Returns the path of static files directory, with a target.
     */
    public function getStaticTargetPath(): string
    {
        $path = $this->getStaticPath();

        if (!empty($this->get('static.target'))) {
            $path = substr($path, 0, -\strlen((string) $this->get('static.target')));
        }

        return $path;
    }

    /**
     * Returns the path of assets files directory.
     */
    public function getAssetsPath(): string
    {
        return Util::joinFile($this->getSourceDir(), (string) $this->get('assets.dir'));
    }

    /**
     * Returns asset image widths.
     */
    public function getAssetsImagesWidths(): array
    {
        return \count((array) $this->get('assets.images.responsive.widths')) > 0 ? (array) $this->get('assets.images.responsive.widths') : [480, 640, 768, 1024, 1366, 1600, 1920];
    }

    /**
     * Returns asset image sizes.
     */
    public function getAssetsImagesSizes(): array
    {
        return \count((array) $this->get('assets.images.responsive.sizes')) > 0 ? (array) $this->get('assets.images.responsive.sizes') : ['default' => '100vw'];
    }

    /**
     * Is cache dir is absolute to system files
     * or relative to project destination?
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
     * Returns cache path.
     *
     * @throws RuntimeException
     */
    public function getCachePath(): string
    {
        if (empty((string) $this->get('cache.dir'))) {
            throw new RuntimeException(sprintf('The cache directory ("%s") is not defined in configuration.', 'cache.dir'));
        }

        if ($this->isCacheDirIsAbsolute()) {
            $cacheDir = Util::joinFile((string) $this->get('cache.dir'), 'cecil');
            Util\File::getFS()->mkdir($cacheDir);

            return $cacheDir;
        }

        return Util::joinFile($this->getDestinationDir(), (string) $this->get('cache.dir'));
    }

    /**
     * Returns cache path of templates.
     */
    public function getCacheTemplatesPath(): string
    {
        return Util::joinFile($this->getCachePath(), (string) $this->get('cache.templates.dir'));
    }

    /**
     * Returns cache path of translations.
     */
    public function getCacheTranslationsPath(): string
    {
        return Util::joinFile($this->getCachePath(), (string) $this->get('cache.translations.dir'));
    }

    /**
     * Returns cache path of assets.
     */
    public function getCacheAssetsPath(): string
    {
        return Util::joinFile($this->getCachePath(), (string) $this->get('cache.assets.dir'));
    }

    /**
     * Returns cache path of remote assets.
     */
    public function getCacheAssetsRemotePath(): string
    {
        return Util::joinFile($this->getCacheAssetsPath(), (string) $this->get('cache.assets.remote.dir'));
    }

    /**
     * Returns the property value of an output format.
     *
     * @throws RuntimeException
     *
     * @return string|array|null
     */
    public function getOutputFormatProperty(string $name, string $property)
    {
        $properties = array_column((array) $this->get('output.formats'), $property, 'name');

        if (empty($properties)) {
            throw new RuntimeException(sprintf('Property "%s" is not defined for format "%s".', $property, $name));
        }

        return $properties[$name] ?? null;
    }

    /**
     * Theme helpers.
     */

    /**
     * Returns theme(s) as an array.
     */
    public function getTheme(): ?array
    {
        if ($themes = $this->get('theme')) {
            if (\is_array($themes)) {
                return $themes;
            }

            return [$themes];
        }

        return null;
    }

    /**
     * Has a (valid) theme(s)?
     *
     * @throws RuntimeException
     */
    public function hasTheme(): bool
    {
        if ($themes = $this->getTheme()) {
            foreach ($themes as $theme) {
                if (!Util\File::getFS()->exists($this->getThemeDirPath($theme, 'layouts')) && !Util\File::getFS()->exists(Util::joinFile($this->getThemesPath(), $theme, 'config.yml'))) {
                    throw new RuntimeException(sprintf('Theme "%s" not found. Did you forgot to install it?', $theme));
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Returns the path of a specific theme's directory.
     * ("layouts" by default).
     */
    public function getThemeDirPath(string $theme, string $dir = 'layouts'): string
    {
        return Util::joinFile($this->getThemesPath(), $theme, $dir);
    }

    /**
     * Language helpers.
     */

    /**
     * Returns an array of available languages.
     *
     * @throws RuntimeException
     */
    public function getLanguages(): array
    {
        if ($this->languages !== null) {
            return $this->languages;
        }

        $languages = (array) $this->get('languages');

        if (!\is_int(array_search($this->getLanguageDefault(), array_column($languages, 'code')))) {
            throw new RuntimeException(sprintf('The default language "%s" is not listed in "languages" key configuration.', $this->getLanguageDefault()));
        }

        $languages = array_filter($languages, function ($language) {
            return !(isset($language['enabled']) && $language['enabled'] === false);
        });

        $this->languages = $languages;

        return $this->languages;
    }

    /**
     * Returns the default language code (ie: "en", "fr-FR", etc.).
     *
     * @throws RuntimeException
     */
    public function getLanguageDefault(): string
    {
        if (!$this->get('language')) {
            throw new RuntimeException('There is no default "language" key in configuration.');
        }

        return $this->get('language');
    }

    /**
     * Returns a language code index.
     *
     * @throws RuntimeException
     */
    public function getLanguageIndex(string $code): int
    {
        $array = array_column($this->getLanguages(), 'code');

        if (false === $index = array_search($code, $array)) {
            throw new RuntimeException(sprintf('The language code "%s" is not defined.', $code));
        }

        return $index;
    }

    /**
     * Returns the property value of a (specified or default) language.
     *
     * @throws RuntimeException
     */
    public function getLanguageProperty(string $property, ?string $code = null): string
    {
        $code = $code ?? $this->getLanguageDefault();

        $properties = array_column($this->getLanguages(), $property, 'code');

        if (empty($properties)) {
            throw new RuntimeException(sprintf('Property "%s" is not defined for language "%s".', $property, $code));
        }

        return $properties[$code];
    }
}

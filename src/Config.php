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

use Cecil\Exception\ConfigException;
use Dflydev\DotAccessData\Data;

/**
 * Class Config.
 */
class Config
{
    /** Configuration is a Data object. */
    protected Data $data;

    /** Default configuration is a Data object. */
    protected Data $default;

    /** Source directory. */
    protected string $sourceDir;

    /** Destination directory. */
    protected string $destinationDir;

    /** Languages list as array. */
    protected ?array $languages = null;

    public const PRESERVE = 0;
    public const REPLACE = 1;
    public const MERGE = 2;
    public const LANG_CODE_PATTERN = '([a-z]{2}(-[A-Z]{2})?)'; // "fr" or "fr-FR"
    public const LANG_LOCALE_PATTERN = '[a-z]{2}(_[A-Z]{2})?(_[A-Z]{2})?'; // "fr" or "fr_FR" or "no_NO_NY"

    /**
     * Build the Config object with the default config + the optional given array.
     */
    public function __construct(?array $config = null)
    {
        // default configuration
        $defaultConfigFile = Util\File::getRealPath('../config/default.php');
        $this->default = new Data(include $defaultConfigFile);

        // base configuration
        $baseConfigFile = Util\File::getRealPath('../config/base.php');
        $this->data = new Data(include $baseConfigFile);

        // import config array if provided
        if ($config !== null) {
            $this->import($config);
        }
    }

    /**
     * Imports (and validate) configuration.
     * The mode can be: Config::PRESERVE, Config::REPLACE or Config::MERGE.
     */
    public function import(array $config, $mode = self::MERGE): void
    {
        $this->data->import($config, $mode);
        $this->setFromEnv(); // override configuration with environment variables
        $this->validate();   // validate configuration
    }

    /**
     * Get configuration as an array.
     */
    public function export(): array
    {
        return $this->data->export();
    }

    /**
     * Is configuration's key exists?
     *
     * @param string $key      Configuration key
     * @param string $language Language code (optional)
     * @param bool   $fallback Set to false to not return the value in the default language as fallback
     */
    public function has(string $key, ?string $language = null, bool $fallback = true): bool
    {
        $default = $this->default->has($key);

        if ($language !== null) {
            $langIndex = $this->getLanguageIndex($language);
            $keyLang = "languages.$langIndex.config.$key";
            if ($this->data->has($keyLang)) {
                return true;
            }
            if ($language !== $this->getLanguageDefault() && $fallback === false) {
                return $default;
            }
        }
        if ($this->data->has($key)) {
            return true;
        }

        return $default;
    }

    /**
     * Get the value of a configuration's key.
     *
     * @param string $key      Configuration key
     * @param string $language Language code (optional)
     * @param bool   $fallback Set to false to not return the value in the default language as fallback
     *
     * @return mixed|null
     */
    public function get(string $key, ?string $language = null, bool $fallback = true)
    {
        $default = $this->default->has($key) ? $this->default->get($key) : null;

        if ($language !== null) {
            $langIndex = $this->getLanguageIndex($language);
            $keyLang = "languages.$langIndex.config.$key";
            if ($this->data->has($keyLang)) {
                return $this->data->get($keyLang);
            }
            if ($language !== $this->getLanguageDefault() && $fallback === false) {
                return $default;
            }
        }
        if ($this->data->has($key)) {
            return $this->data->get($key);
        }

        return $default;
    }

    /**
     * Is an option is enabled?
     * Checks if the key is set to `false` or if subkey `enabled` is set to `false`.
     */
    public function isEnabled(string $key, ?string $language = null, bool $fallback = true): bool
    {
        if ($this->has($key, $language, $fallback) && $this->get($key, $language, $fallback) !== false) {
            return true;
        }
        if ($this->has("$key.enabled", $language, $fallback) && $this->get("$key.enabled", $language, $fallback) === false) {
            return false;
        }

        return false;
    }

    /**
     * Set the source directory.
     *
     * @throws \InvalidArgumentException
     */
    public function setSourceDir(string $sourceDir): self
    {
        if (!is_dir($sourceDir)) {
            throw new \InvalidArgumentException(\sprintf('The directory "%s" is not a valid source.', $sourceDir));
        }
        $this->sourceDir = $sourceDir;

        return $this;
    }

    /**
     * Get the source directory.
     */
    public function getSourceDir(): string
    {
        if ($this->sourceDir === null) {
            return getcwd();
        }

        return $this->sourceDir;
    }

    /**
     * Set the destination directory.
     *
     * @throws \InvalidArgumentException
     */
    public function setDestinationDir(string $destinationDir): self
    {
        if (!is_dir($destinationDir)) {
            throw new \InvalidArgumentException(\sprintf('The directory "%s" is not a valid destination.', $destinationDir));
        }
        $this->destinationDir = $destinationDir;

        return $this;
    }

    /**
     * Get the destination directory.
     */
    public function getDestinationDir(): string
    {
        if ($this->destinationDir === null) {
            return $this->getSourceDir();
        }

        return $this->destinationDir;
    }

    /*
     * Path helpers.
     */

    /**
     * Returns the path of the pages directory.
     */
    public function getPagesPath(): string
    {
        return Util::joinFile($this->getSourceDir(), (string) $this->get('pages.dir'));
    }

    /**
     * Returns the path of the output directory.
     */
    public function getOutputPath(): string
    {
        return Util::joinFile($this->getDestinationDir(), (string) $this->get('output.dir'));
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
     * Returns the path of internal templates directory.
     */
    public function getLayoutsInternalPath(): string
    {
        return __DIR__ . '/../resources/layouts';
    }

    /**
     * Returns the layout for a section.
     */
    public function getLayoutSection(?string $section): ?string
    {
        if ($layout = $this->get('layouts.sections')[$section] ?? null) {
            return $layout;
        }

        return $section;
    }

    /**
     * Returns the path of translations directory.
     */
    public function getTranslationsPath(): string
    {
        return Util::joinFile($this->getSourceDir(), (string) $this->get('layouts.translations.dir'));
    }

    /**
     * Returns the path of internal translations directory.
     */
    public function getTranslationsInternalPath(): string
    {
        return Util\File::getRealPath('../resources/translations');
    }

    /**
     * Returns the path of themes directory.
     */
    public function getThemesPath(): string
    {
        return Util::joinFile($this->getSourceDir(), 'themes');
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
     * Returns the path of remote assets files directory (in cache).
     *
     * @return string
     */
    public function getAssetsRemotePath(): string
    {
        return Util::joinFile($this->getCacheAssetsPath(), 'remote');
    }

    /**
     * Returns cache path.
     *
     * @throws ConfigException
     */
    public function getCachePath(): string
    {
        if (empty((string) $this->get('cache.dir'))) {
            throw new ConfigException(\sprintf('The cache directory (`%s`) is not defined.', 'cache.dir'));
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
        return Util::joinFile($this->getCachePath(), 'templates');
    }

    /**
     * Returns cache path of translations.
     */
    public function getCacheTranslationsPath(): string
    {
        return Util::joinFile($this->getCachePath(), 'translations');
    }

    /**
     * Returns cache path of assets.
     */
    public function getCacheAssetsPath(): string
    {
        return Util::joinFile($this->getCachePath(), 'assets');
    }

    /**
     * Returns cache path of assets files.
     */
    public function getCacheAssetsFilesPath(): string
    {
        return Util::joinFile($this->getCacheAssetsPath(), 'files');
    }

    /*
     * Output helpers.
     */

    /**
     * Returns the property value of an output format.
     *
     * @throws ConfigException
     */
    public function getOutputFormatProperty(string $name, string $property): string|array|null
    {
        $properties = array_column((array) $this->get('output.formats'), $property, 'name');

        if (empty($properties)) {
            throw new ConfigException(\sprintf('Property "%s" is not defined for format "%s".', $property, $name));
        }

        return $properties[$name] ?? null;
    }

    /*
     * Assets helpers.
     */

    /**
     * Returns asset image widths.
     * Default: [480, 640, 768, 1024, 1366, 1600, 1920].
     */
    public function getAssetsImagesWidths(): array
    {
        return $this->get('assets.images.responsive.widths') ?? [480, 640, 768, 1024, 1366, 1600, 1920];
    }

    /**
     * Returns asset image sizes.
     * Default: ['default' => '100vw'].
     */
    public function getAssetsImagesSizes(): array
    {
        return $this->get('assets.images.responsive.sizes') ?? ['default' => '100vw'];
    }

    /*
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
     * @throws ConfigException
     */
    public function hasTheme(): bool
    {
        if ($themes = $this->getTheme()) {
            foreach ($themes as $theme) {
                if (!Util\File::getFS()->exists($this->getThemeDirPath($theme, 'layouts')) && !Util\File::getFS()->exists(Util::joinFile($this->getThemesPath(), $theme, 'config.yml'))) {
                    throw new ConfigException(\sprintf('Theme "%s" not found. Did you forgot to install it?', $theme));
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

    /*
     * Language helpers.
     */

    /**
     * Returns an array of available languages.
     *
     * @throws ConfigException
     */
    public function getLanguages(): array
    {
        if ($this->languages !== null) {
            return $this->languages;
        }

        $languages = array_filter((array) $this->get('languages'), function ($language) {
            return !(isset($language['enabled']) && $language['enabled'] === false);
        });

        if (!\is_int(array_search($this->getLanguageDefault(), array_column($languages, 'code')))) {
            throw new ConfigException(\sprintf('The default language "%s" is not listed in "languages".', $this->getLanguageDefault()));
        }

        $this->languages = $languages;

        return $this->languages;
    }

    /**
     * Returns the default language code (ie: "en", "fr-FR", etc.).
     *
     * @throws ConfigException
     */
    public function getLanguageDefault(): string
    {
        if (!$this->get('language')) {
            throw new ConfigException('There is no default "language" key.');
        }
        if (\is_array($this->get('language'))) {
            if (!$this->get('language.code')) {
                throw new ConfigException('There is no "language.code" key.');
            }

            return $this->get('language.code');
        }

        return $this->get('language');
    }

    /**
     * Returns a language code index.
     *
     * @throws ConfigException
     */
    public function getLanguageIndex(string $code): int
    {
        $array = array_column($this->getLanguages(), 'code');

        if (false === $index = array_search($code, $array)) {
            throw new ConfigException(\sprintf('The language code "%s" is not defined.', $code));
        }

        return $index;
    }

    /**
     * Returns the property value of a (specified or the default) language.
     *
     * @throws ConfigException
     */
    public function getLanguageProperty(string $property, ?string $code = null): string
    {
        $code = $code ?? $this->getLanguageDefault();

        $properties = array_column($this->getLanguages(), $property, 'code');

        if (empty($properties)) {
            throw new ConfigException(\sprintf('Property "%s" is not defined for language "%s".', $property, $code));
        }

        return $properties[$code];
    }

    /*
     * Cache helpers.
     */

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
     * Set configuration from environment variables starting with "CECIL_".
     */
    private function setFromEnv(): void
    {
        foreach (getenv() as $key => $value) {
            if (str_starts_with($key, 'CECIL_')) {
                $this->data->set(str_replace(['cecil_', '_'], ['', '.'], strtolower($key)), $this->castSetValue($value));
            }
        }
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
        $filteredValue = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if ($filteredValue !== null) {
            return $filteredValue;
        }

        return $value;
    }

    /**
     * Validate the configuration.
     *
     * @throws ConfigException
     */
    private function validate(): void
    {
        // default language must be valid
        if (!preg_match('/^' . Config::LANG_CODE_PATTERN . '$/', $this->getLanguageDefault())) {
            throw new ConfigException(\sprintf('Default language code "%s" is not valid (e.g.: "language: fr-FR").', $this->getLanguageDefault()));
        }
        // if language is set then the locale is required and must be valid
        foreach ((array) $this->get('languages') as $lang) {
            if (!isset($lang['locale'])) {
                throw new ConfigException('A language locale is not defined.');
            }
            if (!preg_match('/^' . Config::LANG_LOCALE_PATTERN . '$/', $lang['locale'])) {
                throw new ConfigException(\sprintf('The language locale "%s" is not valid (e.g.: "locale: fr_FR").', $lang['locale']));
            }
        }

        // check for deprecated options
        $deprecatedConfigFile = Util\File::getRealPath('../config/deprecated.php');
        $deprecatedConfig = require $deprecatedConfigFile;
        array_walk($deprecatedConfig, function ($to, $from) {
            if ($this->has($from)) {
                if (empty($to)) {
                    throw new ConfigException("Option `$from` is deprecated and must be removed.");
                }
                $path = explode(':', $to);
                $step = 0;
                $formatedPath = '';
                foreach ($path as $fragment) {
                    $step = $step + 2;
                    $formatedPath .= "$fragment:\n" . str_pad(' ', $step);
                }
                throw new ConfigException("Option `$from` must be moved to:\n```\n$formatedPath\n```");
            }
        });
    }
}

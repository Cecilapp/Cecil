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

namespace Cecil;

use Cecil\Exception\ConfigException;
use Dflydev\DotAccessData\Data;
use Dflydev\DotAccessData\Exception\DataException;
use Nette\Schema\Expect;
use Nette\Schema\Processor;
use Nette\Schema\Schema;
use Nette\Schema\ValidationException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Configuration class.
 *
 * This class is used to manage the configuration of the application.
 * It allows to import, export, validate and access configuration data.
 * It also provides methods to handle paths, languages, themes, and cache.
 */
class Config
{
    public const int IMPORT_PRESERVE = 0;
    public const int IMPORT_REPLACE = 1;
    public const int IMPORT_MERGE = 2;
    public const string LANG_CODE_PATTERN = '([a-z]{2}(-[A-Z]{2})?)'; // "fr" or "fr-FR"
    public const string LANG_LOCALE_PATTERN = '[a-z]{2}(_[A-Z]{2})?(_[A-Z]{2})?'; // "fr" or "fr_FR" or "no_NO_NY"
    /**
     * Lists of items merged by identifier, instead of being concatenated.
     */
    private const array LIST_IDENTIFIERS = [
        'output.formats' => 'name',
    ];

    /**
     * Effective configuration: the default configuration, overridden by the themes configuration,
     * itself overridden by the site configuration and the environment variables.
     * This is a Data object, which allows to use dot notation to access configuration keys.
     * For example: $config->get('key.subkey') or $config->has('key.subkey').
     * @var Data
     */
    protected Data $data;
    /**
     * Default configuration.
     */
    protected Data $default;
    /**
     * Themes configuration layer.
     * @var array<string, mixed>
     */
    protected array $themes = [];
    /**
     * Site configuration layer.
     * @var array<string, mixed>
     */
    protected array $site = [];
    /**
     * Source directory.
     * This is the directory where the source files are located.
     * It is used to resolve relative paths in the configuration.
     * If not set, it defaults to the current working directory.
     * @var string|null
     */
    protected ?string $sourceDir = null;
    /**
     * Destination directory.
     * This is the directory where the output files will be generated.
     * It is used to resolve relative paths in the configuration.
     * If not set, it defaults to the source directory.
     * @var string|null
     */
    protected ?string $destinationDir = null;
    /**
     * Languages list as array.
     * This is used to store the languages defined in the configuration.
     * It is initialized to null and will be populated when the languages are requested.
     * @var array<int, array<string, mixed>>|null
     * @see Config::getLanguages()
     * @see Config::getLanguageDefault()
     */
    protected ?array $languages = null;

    /**
     * Build the Config object with the default config + the optional given array.
     *
     * @param array<string, mixed>|null $config
     */
    public function __construct(?array $config = null)
    {
        // default configuration
        $defaultConfigFile = Util\File::getRealPath('../config/default.php');
        $this->default = new Data(include $defaultConfigFile);
        $this->data = new Data($this->default->export());
        // import config array if provided
        if ($config !== null) {
            $this->import($config);
        }
    }

    /**
     * Imports (and validate) configuration.
     * The mode can be:
     * - Config::IMPORT_PRESERVE: imports a theme configuration, overridden by the site configuration and by previously imported themes.
     * - Config::IMPORT_REPLACE: imports a site configuration, replacing existing lists of items.
     * - Config::IMPORT_MERGE: imports a site configuration, concatenating existing lists of items.
     *
     * The effective configuration is the default configuration, overridden by the themes configuration,
     * itself overridden by the site configuration and by the environment variables.
     *
     * Maps are merged key by key, lists of values are replaced as a whole (combined between themes and site),
     * and a map of booleans applied to a list of values adds (`true`) or removes (`false`) items.
     * @see Config::mergeValues()
     *
     * @param array<string, mixed> $config Configuration array to import
     * @param self::IMPORT_* $mode Import mode (default: Config::IMPORT_MERGE)
     * @param string|null    $source Source of the configuration (ie: file path), used in error messages
     *
     * @throws ConfigException
     */
    public function import(array $config, int $mode = self::IMPORT_MERGE, ?string $source = null): void
    {
        // deprecated options are checked before merging, to report their actual source
        self::checkDeprecated($config, $source);
        self::checkDeprecated($this->getEnvConfig(), 'environment variables', true);
        if ($mode === self::IMPORT_PRESERVE) {
            $this->themes = self::merge($config, $this->themes, self::IMPORT_PRESERVE);
        } else {
            $this->site = self::merge($this->site, $config, $mode);
        }
        $this->data = new Data(self::merge(
            $this->default->export(),
            self::merge($this->themes, $this->site, self::IMPORT_PRESERVE),
            self::IMPORT_REPLACE
        ));
        $this->languages = null;
        $this->setFromEnv(); // override configuration with environment variables
        $this->validate();   // validate configuration
    }

    /**
     * Get configuration as an array.
     *
     * @return array<string, mixed>
     */
    public function export(): array
    {
        return $this->data->export();
    }

    /**
     * Loads and parse a YAML file.
     *
     * @return array<string, mixed>
     */
    public static function loadFile(string $file, bool $ignore = false): array
    {
        if (!Util\File::getFS()->exists($file)) {
            if ($ignore) {
                return [];
            }
            throw new ConfigException(\sprintf('File "%s" does not exist.', $file));
        }
        if (false === $fileContent = Util\File::fileGetContents($file)) {
            throw new ConfigException(\sprintf('Unable to read file "%s".', $file));
        }
        try {
            return Yaml::parse($fileContent, Yaml::PARSE_DATETIME) ?? [];
        } catch (ParseException $e) {
            throw new ConfigException(\sprintf('"%s" parsing error: %s', $file, $e->getMessage()));
        }
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
        if ($language !== null) {
            $langIndex = $this->getLanguageIndex($language);
            if ($this->data->has("languages.$langIndex.config.$key")) {
                return true;
            }
            if ($language !== $this->getLanguageDefault() && $fallback === false) {
                return $this->default->has($key);
            }
        }

        return $this->data->has($key);
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
        $value = $this->data->has($key) ? $this->data->get($key) : null;
        if ($language !== null) {
            if ($language !== $this->getLanguageDefault() && $fallback === false) {
                $value = $this->getDefault($key);
            }
            $langIndex = $this->getLanguageIndex($language);
            $keyLang = "languages.$langIndex.config.$key";
            if ($this->data->has($keyLang)) {
                // localized value overrides the global (or default) value
                return $value === null
                    ? $this->data->get($keyLang)
                    : self::mergeValues($value, $this->data->get($keyLang), self::IMPORT_REPLACE, $key);
            }
        }

        return $value;
    }

    /**
     * Is an option is enabled?
     * Checks if the key is set to `false` or if subkey `enabled` is set to `false`.
     */
    public function isEnabled(string $key, ?string $language = null, bool $fallback = true): bool
    {
        if ($this->has($key, $language, $fallback) && $this->get($key, $language, $fallback) === true) {
            return true;
        }
        if ($this->has("$key.enabled", $language, $fallback)) {
            if ($this->get("$key.enabled", $language, $fallback) === true) {
                return true;
            }
            return false;
        }
        if ($this->has($key, $language, $fallback) && $this->get($key, $language, $fallback) !== false) {
            return true;
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
            return getcwd() ?: throw new ConfigException('Unable to get current working directory.');
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
        return Util\File::getRealPath('../resources/layouts');
    }

    /**
     * Returns the templates directories, by priority: site, themes, then internal.
     *
     * @return list<string>
     */
    public function getLayoutsPaths(): array
    {
        $paths = [];

        // layouts/
        if (is_dir($this->getLayoutsPath())) {
            $paths[] = $this->getLayoutsPath();
        }
        // <theme>/layouts/
        if ($this->hasTheme()) {
            foreach ($this->getTheme() ?? [] as $theme) {
                $paths[] = $this->getThemeDirPath($theme);
            }
        }
        // resources/layouts/
        if (is_dir($this->getLayoutsInternalPath())) {
            $paths[] = $this->getLayoutsInternalPath();
        }

        return $paths;
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

    /*
     * Output helpers.
     */

    /**
     * Returns the property value of an output format.
     *
     * @return string|array<int, string>|null
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
     *
     * @return array<int, int>
     */
    public function getAssetsImagesWidths(): array
    {
        return $this->get('assets.images.responsive.widths');
    }

    /**
     * Returns asset image sizes.
     *
     * @return array<string, string>
     */
    public function getAssetsImagesSizes(): array
    {
        return $this->get('assets.images.responsive.sizes');
    }

    /**
     * Returns asset image densities.
     *
     * @return array<int, int|float>
     */
    public function getAssetsImagesDensities(): array
    {
        return $this->get('assets.images.responsive.densities');
    }

    /*
     * Theme helpers.
     */

    /**
     * Returns theme(s) as an array.
     *
     * @return array<int, string>|null
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
     * @return array<int, array<string, mixed>>
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
        if (Util\File::getFS()->isAbsolutePath($path)) {
            return true;
        }

        return false;
    }

    /*
     * Private functions.
     */

    /**
     * Merges two configuration arrays, key by key: values of `$high` take precedence over values of `$low`.
     *
     * @param array<mixed>   $low  Lower priority configuration
     * @param array<mixed>   $high Higher priority configuration
     * @param self::IMPORT_* $mode Import mode
     *
     * @return array<mixed>
     */
    private static function merge(array $low, array $high, int $mode, string $path = ''): array
    {
        foreach ($high as $key => $value) {
            if (!\array_key_exists($key, $low)) {
                $low[$key] = $value;
                continue;
            }
            $low[$key] = self::mergeValues($low[$key], $value, $mode, ltrim("$path.$key", '.'));
        }

        return $low;
    }

    /**
     * Merges two values, `$high` taking precedence over `$low`:
     * - scalars are replaced;
     * - a boolean applied to a map enables or disables it (ie: `cache: false`), the map is kept;
     * - lists of values are replaced as a whole, except in preserve mode (ie: theme configuration) where they are combined;
     * - a map of booleans applied to a list of values adds (`true`) or removes (`false`) items;
     * - lists of identified items (ie: `output.formats`) are merged by identifier;
     * - other lists of items (ie: menus entries) are concatenated in merge mode, replaced otherwise;
     * - maps are merged key by key.
     *
     * @param self::IMPORT_* $mode Import mode
     */
    private static function mergeValues(mixed $low, mixed $high, int $mode, string $path): mixed
    {
        if (\is_bool($high) && \is_array($low) && $low !== [] && !array_is_list($low)) {
            return array_merge($low, ['enabled' => $high]);
        }
        if (!\is_array($high) || !\is_array($low)) {
            return $high;
        }
        if (self::isListPatch($high) && self::isValuesList($low)) {
            return self::applyListPatch($low, $high);
        }
        if (array_is_list($high) && array_is_list($low)) {
            if (isset(self::LIST_IDENTIFIERS[$path])) {
                return self::mergeListById($low, $high, self::LIST_IDENTIFIERS[$path]);
            }
            if (self::isValuesList($high) && self::isValuesList($low)) {
                // values required by a theme are kept: they can only be removed with a map of booleans
                return $mode === self::IMPORT_PRESERVE ? array_values(array_unique(array_merge($high, $low))) : $high;
            }
            if ($mode === self::IMPORT_MERGE) {
                return array_merge($low, $high);
            }

            return $high;
        }
        if (array_is_list($high) || array_is_list($low)) {
            // an empty array (ie: `{}` in YAML) doesn't override a map
            return $high === [] ? $low : $high;
        }

        return self::merge($low, $high, $mode, $path);
    }

    /**
     * Merges two lists of items by identifier: items of `$high` override items of `$low` with the same identifier.
     *
     * @param list<mixed> $low
     * @param list<mixed> $high
     *
     * @return list<mixed>
     */
    private static function mergeListById(array $low, array $high, string $id): array
    {
        $ids = array_map(fn ($item) => \is_array($item) ? ($item[$id] ?? null) : null, $low);
        foreach ($high as $item) {
            $index = \is_array($item) && isset($item[$id]) ? array_search($item[$id], $ids, true) : false;
            if ($index === false || !\is_array($low[$index])) {
                $low[] = $item;
                continue;
            }
            $low[$index] = self::merge($low[$index], $item, self::IMPORT_REPLACE);
        }

        return $low;
    }


    /**
     * Returns the default value of a configuration key, if any.
     */
    private function getDefault(string $key): mixed
    {
        try {
            return $this->default->has($key) ? $this->default->get($key) : null;
        } catch (DataException) {
            return null;
        }
    }

    /**
     * Is the value a list of values (strings or integers)?
     *
     * @phpstan-assert-if-true list<string|int> $value
     */
    private static function isValuesList(mixed $value): bool
    {
        if (!\is_array($value) || !array_is_list($value)) {
            return false;
        }
        foreach ($value as $item) {
            if (!\is_string($item) && !\is_int($item)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Is the value a list patch (ie: `{rss: true, atom: false}`)?
     *
     * @phpstan-assert-if-true array<string|int, bool> $value
     */
    private static function isListPatch(mixed $value): bool
    {
        if (!\is_array($value) || array_is_list($value)) {
            return false;
        }
        foreach ($value as $item) {
            if (!\is_bool($item)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Applies a list patch to a list of values: adds items set to `true` and removes items set to `false`.
     *
     * @param list<string|int>         $list
     * @param array<string|int, bool> $patch
     *
     * @return list<string|int>
     */
    private static function applyListPatch(array $list, array $patch): array
    {
        $list = array_values(array_filter($list, fn ($item) => ($patch[$item] ?? null) !== false));
        $items = array_flip($list);
        foreach ($patch as $item => $enabled) {
            if ($enabled && !isset($items[$item])) {
                $list[] = $item;
            }
        }

        return $list;
    }

    /**
     * Set configuration from environment variables starting with "CECIL_".
     */
    private function setFromEnv(): void
    {
        foreach ($this->getEnvConfig() as $key => $value) {
            $current = $this->data->has($key) ? $this->data->get($key) : null;
            $this->data->set($key, self::mergeValues($current, $value, self::IMPORT_REPLACE, $key));
        }
    }

    /**
     * Returns configuration from environment variables starting with "CECIL_", indexed by configuration key
     * (ie: `CECIL_PAGES_DIR` -> `pages.dir`).
     *
     * @return array<string, mixed>
     */
    private function getEnvConfig(): array
    {
        $config = [];
        foreach (array_merge(getenv() ?: [], $_ENV) as $key => $value) {
            if (str_starts_with((string) $key, 'CECIL_')) {
                $config[str_replace(['cecil_', '_'], ['', '.'], strtolower((string) $key))] = $this->castSetValue($value);
            }
        }

        return $config;
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
        // structural validation of well-defined sections
        $this->validateSchema();

        // default language must be valid
        if (!preg_match('/^' . Config::LANG_CODE_PATTERN . '$/', $this->getLanguageDefault())) {
            throw new ConfigException(\sprintf('Default language code "%s" is not valid (e.g.: "language: fr-FR").', $this->getLanguageDefault()));
        }
    }

    /**
     * Checks a configuration for deprecated options, and reports all of them at once.
     * Keys of `$config` can be nested arrays or dot notation paths (ie: environment variables).
     *
     * @param array<string, mixed> $config
     * @param bool                 $env    Options are environment variables (ie: `CECIL_PAGES_DIR`)
     *
     * @throws ConfigException
     */
    private static function checkDeprecated(array $config, ?string $source = null, bool $env = false): void
    {
        if ($config === []) {
            return;
        }
        $data = new Data();
        foreach ($config as $key => $value) {
            $data->set((string) $key, $value);
        }
        $errors = [];
        $deprecated = require Util\File::getRealPath('../config/deprecated.php');
        foreach ($deprecated as $from => $to) {
            try {
                if (!$data->has($from)) {
                    continue;
                }
            } catch (DataException) {
                continue;
            }
            if ($env) {
                $variable = fn (string $key) => 'CECIL_' . strtoupper(str_replace('.', '_', $key));
                $errors[] = empty($to)
                    ? \sprintf('- Variable `%s` is deprecated and must be removed.', $variable($from))
                    : \sprintf('- Variable `%s` must be renamed to `%s`.', $variable($from), $variable($to));
                continue;
            }
            if (empty($to)) {
                $errors[] = "- Option `$from` is deprecated and must be removed.";
                continue;
            }
            // builds the new configuration snippet, with the current value
            $snippet = $data->get($from);
            foreach (array_reverse(explode('.', $to)) as $fragment) {
                $snippet = [$fragment => $snippet];
            }
            $yaml = rtrim(Yaml::dump($snippet, 10, 2));
            $errors[] = "- Option `$from` must be moved to:\n  ```\n  " . str_replace("\n", "\n  ", $yaml) . "\n  ```";
        }
        if ($errors !== []) {
            throw new ConfigException(\sprintf(
                "Deprecated configuration option(s)%s:\n%s",
                $source !== null ? " in $source" : '',
                implode("\n", $errors)
            ));
        }
    }

    /**
     * Validates well-defined configuration sections against a schema.
     * Only targeted sections are processed: the rest of the configuration stays open
     * (menus, taxonomies, custom variables, etc.).
     *
     * @throws ConfigException
     */
    private function validateSchema(): void
    {
        $processor = new Processor();
        foreach ($this->getSchemas() as $key => $schema) {
            if (!$this->has($key)) {
                continue;
            }
            try {
                $processor->process($schema, $this->get($key));
            } catch (ValidationException $e) {
                throw new ConfigException($e->getMessage(), previous: $e);
            }
        }
    }

    /**
     * Returns the validation schemas indexed by configuration key.
     *
     * @return array<string, Schema>
     */
    private function getSchemas(): array
    {
        return [
            // main site options
            'title'        => Expect::string(),
            'baseline'     => Expect::string(),
            'baseurl'      => Expect::string(),
            'canonicalurl' => Expect::bool(),
            'description'  => Expect::string(),
            // `theme: <name>` shorthand or a list of theme names
            'theme' => Expect::anyOf(
                Expect::string(),
                Expect::listOf('string')
            ),
            // `cache: true|false` shorthand or a structure with a non-empty `dir` when enabled
            'cache' => Expect::anyOf(
                Expect::bool(),
                Expect::structure([
                    'dir' => Expect::string(),
                ])->otherItems(Expect::mixed())->assert(
                    fn ($cache) => !$this->isEnabled('cache') || trim((string) ($cache->dir ?? '')) !== '',
                    'The cache directory (`cache.dir`) must not be empty when cache is enabled.'
                )
            ),
            // each output format must define a `name` and a `mediatype`
            'output.formats' => Expect::arrayOf(
                Expect::structure([
                    'name'      => Expect::string()->required()->assert(fn ($value) => trim((string) $value) !== '', 'Output format "name" must not be empty.'),
                    'mediatype' => Expect::string()->required()->assert(fn ($value) => trim((string) $value) !== '', 'Output format "mediatype" must not be empty.'),
                ])->otherItems(Expect::mixed())
            ),
            // formats applied by page type (each type accepts a list of format names)
            'output.pagetypeformats' => Expect::structure([
                'page'       => Expect::listOf('string'),
                'homepage'   => Expect::listOf('string'),
                'section'    => Expect::listOf('string'),
                'vocabulary' => Expect::listOf('string'),
                'term'       => Expect::listOf('string'),
            ])->otherItems(Expect::mixed()),
            // each language must define a `code` and a valid `locale`
            'languages' => Expect::listOf(
                Expect::structure([
                    'code'   => Expect::string()->required(),
                    'locale' => Expect::string()->required()->pattern(Config::LANG_LOCALE_PATTERN),
                ])->otherItems(Expect::mixed())
            ),
            // date formatting options
            'date' => Expect::structure([
                'format'   => Expect::string(),
                'timezone' => Expect::string(),
            ])->otherItems(Expect::mixed()),
            // pages management options
            'pages' => Expect::structure([
                'dir'         => Expect::string(),
                'ext'         => Expect::listOf('string'),
                'frontmatter' => Expect::anyOf('yaml', 'ini', 'toml', 'json'),
            ])->otherItems(Expect::mixed()),
            // data files options
            'data' => Expect::structure([
                'dir'  => Expect::string(),
                'ext'  => Expect::listOf('string'),
                'load' => Expect::bool(),
            ])->otherItems(Expect::mixed()),
            // static files options
            'static' => Expect::structure([
                'dir'    => Expect::string(),
                'target' => Expect::string(),
                'load'   => Expect::bool(),
            ])->otherItems(Expect::mixed()),
            // `optimize: true|false` shorthand or a structure enabling optimization by file type
            'optimize' => Expect::anyOf(
                Expect::bool(),
                Expect::structure([
                    'enabled' => Expect::bool(),
                    'html'    => Expect::structure([
                        'enabled' => Expect::bool(),
                        'ext'     => Expect::listOf('string'),
                    ])->otherItems(Expect::mixed()),
                    'css' => Expect::structure([
                        'enabled' => Expect::bool(),
                        'ext'     => Expect::listOf('string'),
                    ])->otherItems(Expect::mixed()),
                    'js' => Expect::structure([
                        'enabled' => Expect::bool(),
                        'ext'     => Expect::listOf('string'),
                    ])->otherItems(Expect::mixed()),
                    'images' => Expect::structure([
                        'enabled' => Expect::bool(),
                        'ext'     => Expect::listOf('string'),
                    ])->otherItems(Expect::mixed()),
                ])->otherItems(Expect::mixed())
            ),
            // local preview server custom HTTP headers
            'server.headers' => Expect::listOf(
                Expect::structure([
                    'path'    => Expect::string()->required(),
                    'headers' => Expect::listOf(
                        Expect::structure([
                            'key'   => Expect::string()->required(),
                            'value' => Expect::scalar()->required(),
                        ])->otherItems(Expect::mixed())
                    )->required(),
                ])->otherItems(Expect::mixed())
            ),
        ];
    }
}

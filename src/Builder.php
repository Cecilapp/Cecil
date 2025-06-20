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

use Cecil\Collection\Page\Collection as PagesCollection;
use Cecil\Exception\RuntimeException;
use Cecil\Generator\GeneratorManager;
use Cecil\Logger\PrintLogger;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\Finder;

/**
 * The main Cecil builder class.
 *
 * This class is responsible for building the website by processing various steps,
 * managing configuration, and handling content, data, static files, pages, assets,
 * menus, taxonomies, and rendering.
 * It also provides methods for logging, debugging, and managing build metrics.
 */
class Builder implements LoggerAwareInterface
{
    public const VERSION = '8.x-dev';
    public const VERBOSITY_QUIET = -1;
    public const VERBOSITY_NORMAL = 0;
    public const VERBOSITY_VERBOSE = 1;
    public const VERBOSITY_DEBUG = 2;
    /**
     * Default options for the build process.
     * These options can be overridden when calling the build() method.
     * - 'drafts': if true, builds drafts too (default: false)
     * - 'dry-run': if true, generated files are not saved (default: false)
     * - 'page': if specified, only this page is processed (default: '')
     * - 'render-subset': limits the render step to a specific subset (default: '')
     * @var array<string, bool|string>
     * @see \Cecil\Builder::build()
     */
    public const OPTIONS = [
        'drafts'  => false,
        'dry-run' => false,
        'page'    => '',
        'render-subset' => '',
    ];

    /**
     * Steps processed by build(), in order.
     * These steps are executed sequentially to build the website.
     * Each step is a class that implements the StepInterface.
     * @var array<string>
     * @see \Cecil\Step\StepInterface
     */
    protected $steps = [
        'Cecil\Step\Pages\Load',
        'Cecil\Step\Data\Load',
        'Cecil\Step\StaticFiles\Load',
        'Cecil\Step\Pages\Create',
        'Cecil\Step\Pages\Convert',
        'Cecil\Step\Taxonomies\Create',
        'Cecil\Step\Pages\Generate',
        'Cecil\Step\Menus\Create',
        'Cecil\Step\StaticFiles\Copy',
        'Cecil\Step\Pages\Render',
        'Cecil\Step\Pages\Save',
        'Cecil\Step\Assets\Save',
        'Cecil\Step\Optimize\Html',
        'Cecil\Step\Optimize\Css',
        'Cecil\Step\Optimize\Js',
        'Cecil\Step\Optimize\Images',
    ];
    /**
     * Configuration object.
     * This object holds all the configuration settings for the build process.
     * It can be set to an array or a Config instance.
     * @var Config|array|null
     * @see \Cecil\Config
     */
    protected $config;
    /**
     * Logger instance.
     * This logger is used to log messages during the build process.
     * It can be set to any PSR-3 compliant logger.
     * @var LoggerInterface
     * @see \Psr\Log\LoggerInterface
     * */
    protected $logger;
    /**
     * Debug mode state.
     * If true, debug messages are logged.
     * @var bool
     */
    protected $debug = false;
    /**
     * Build options.
     * These options can be passed to the build() method to customize the build process.
     * @var array
     * @see \Cecil\Builder::OPTIONS
     * @see \Cecil\Builder::build()
     */
    protected $options = [];
    /**
     * Content files collection.
     * This is a Finder instance that collects all the content files (pages, posts, etc.) from the source directory.
     * @var Finder
     */
    protected $content;
    /**
     * Data collection.
     * This is an associative array that holds data loaded from YAML files in the data directory.
     * @var array
     */
    protected $data = [];
    /**
     * Static files collection.
     * This is an associative array that holds static files (like images, CSS, JS) that are copied to the destination directory.
     * @var array
     */
    protected $static = [];
    /**
     * Pages collection.
     * This is a collection of pages that have been processed and are ready for rendering.
     * It is an instance of PagesCollection, which is a custom collection class for managing pages.
     * @var PagesCollection
     */
    protected $pages;
    /**
     * Assets path collection.
     * This is an array that holds paths to assets (like CSS, JS, images) that are used in the build process.
     * It is used to keep track of assets that need to be processed or copied.
     * It can be set to an array of paths or updated with new asset paths.
     * @var array
     */
    protected $assets = [];
    /**
     * Menus collection.
     * This is an associative array that holds menus for different languages.
     * Each key is a language code, and the value is a Collection\Menu\Collection instance
     * that contains the menu items for that language.
     * It is used to manage navigation menus across different languages in the website.
     * @var array
     * @see \Cecil\Collection\Menu\Collection
     */
    protected $menus;
    /**
     * Taxonomies collection.
     * This is an associative array that holds taxonomies for different languages.
     * Each key is a language code, and the value is a Collection\Taxonomy\Collection instance
     * that contains the taxonomy terms for that language.
     * It is used to manage taxonomies (like categories, tags) across different languages in the website.
     * @var array
     * @see \Cecil\Collection\Taxonomy\Collection
     */
    protected $taxonomies;
    /**
     * Renderer.
     * This is an instance of Renderer\RendererInterface that is responsible for rendering pages.
     * It handles the rendering of templates and the application of data to those templates.
     * @var Renderer\RendererInterface
     */
    protected $renderer;
    /**
     * Generators manager.
     * This is an instance of GeneratorManager that manages all the generators used in the build process.
     * Generators are used to create dynamic content or perform specific tasks during the build.
     * It allows for the registration and execution of various generators that can extend the functionality of the build process.
     * @var GeneratorManager
     */
    protected $generatorManager;
    /**
     * Application version.
     * @var string
     */
    protected static $version;
    /**
     * Build metrics.
     * This array holds metrics about the build process, such as duration and memory usage for each step.
     * It is used to track the performance of the build and can be useful for debugging and optimization.
     * @var array
     */
    protected $metrics = [];
    /**
     * Current build ID.
     * This is a unique identifier for the current build process.
     * It is generated based on the current date and time when the build starts.
     * It can be used to track builds, especially in environments where multiple builds may occur.
     * @var string
     * @see \Cecil\Builder::build()
     */
    protected $buildId;

    /**
     * @param Config|array|null    $config
     * @param LoggerInterface|null $logger
     */
    public function __construct($config = null, ?LoggerInterface $logger = null)
    {
        // init and set config
        $this->config = new Config();
        if ($config !== null) {
            $this->setConfig($config);
        }
        // debug mode?
        if (getenv('CECIL_DEBUG') == 'true' || $this->getConfig()->isEnabled('debug')) {
            $this->debug = true;
        }
        // set logger
        if ($logger === null) {
            $logger = new PrintLogger(self::VERBOSITY_VERBOSE);
        }
        $this->setLogger($logger);
    }

    /**
     * Creates a new Builder instance.
     */
    public static function create(): self
    {
        $class = new \ReflectionClass(\get_called_class());

        return $class->newInstanceArgs(\func_get_args());
    }

    /**
     * Builds a new website.
     * This method processes the build steps in order, collects content, data, static files,
     * generates pages, renders them, and saves the output to the destination directory.
     * It also collects metrics about the build process, such as duration and memory usage.
     * @param array<self::OPTIONS> $options
     * @see \Cecil\Builder::OPTIONS
     */
    public function build(array $options): self
    {
        // set start script time and memory usage
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        // checks soft errors
        $this->checkErrors();

        // merge options with defaults
        $this->options = array_merge(self::OPTIONS, $options);

        // set build ID
        $this->buildId = date('YmdHis');

        // process each step
        $steps = [];
        // init...
        foreach ($this->steps as $step) {
            /** @var Step\StepInterface $stepObject */
            $stepObject = new $step($this);
            $stepObject->init($this->options);
            if ($stepObject->canProcess()) {
                $steps[] = $stepObject;
            }
        }
        // ...and process
        $stepNumber = 0;
        $stepsTotal = \count($steps);
        foreach ($steps as $step) {
            $stepNumber++;
            /** @var Step\StepInterface $step */
            $this->getLogger()->notice($step->getName(), ['step' => [$stepNumber, $stepsTotal]]);
            $stepStartTime = microtime(true);
            $stepStartMemory = memory_get_usage();
            $step->process();
            // step duration and memory usage
            $this->metrics['steps'][$stepNumber]['name'] = $step->getName();
            $this->metrics['steps'][$stepNumber]['duration'] = Util::convertMicrotime((float) $stepStartTime);
            $this->metrics['steps'][$stepNumber]['memory']   = Util::convertMemory(memory_get_usage() - $stepStartMemory);
            $this->getLogger()->info(\sprintf(
                '%s done in %s (%s)',
                $this->metrics['steps'][$stepNumber]['name'],
                $this->metrics['steps'][$stepNumber]['duration'],
                $this->metrics['steps'][$stepNumber]['memory']
            ));
        }
        // build duration and memory usage
        $this->metrics['total']['duration'] = Util::convertMicrotime($startTime);
        $this->metrics['total']['memory']   = Util::convertMemory(memory_get_usage() - $startMemory);
        $this->getLogger()->notice(\sprintf('Built in %s (%s)', $this->metrics['total']['duration'], $this->metrics['total']['memory']));

        return $this;
    }

    /**
     * Returns current build ID.
     */
    public function getBuildId(): string
    {
        return $this->buildId;
    }

    /**
     * Set configuration.
     */
    public function setConfig(array|Config $config): self
    {
        if (\is_array($config)) {
            $config = new Config($config);
        }
        if ($this->config !== $config) {
            $this->config = $config;
        }

        // import themes configuration
        $this->importThemesConfig();
        // autoloads local extensions
        Util::autoload($this, 'extensions');

        return $this;
    }

    /**
     * Returns configuration.
     */
    public function getConfig(): Config
    {
        if ($this->config === null) {
            $this->config = new Config();
        }

        return $this->config;
    }

    /**
     * Config::setSourceDir() alias.
     */
    public function setSourceDir(string $sourceDir): self
    {
        $this->getConfig()->setSourceDir($sourceDir);
        // import themes configuration
        $this->importThemesConfig();

        return $this;
    }

    /**
     * Config::setDestinationDir() alias.
     */
    public function setDestinationDir(string $destinationDir): self
    {
        $this->getConfig()->setDestinationDir($destinationDir);

        return $this;
    }

    /**
     * Import themes configuration.
     */
    public function importThemesConfig(): void
    {
        foreach ((array) $this->getConfig()->get('theme') as $theme) {
            $this->getConfig()->import(
                Config::loadFile(Util::joinFile($this->getConfig()->getThemesPath(), $theme, 'config.yml'), true),
                Config::IMPORT_PRESERVE
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * Returns the logger instance.
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Returns debug mode state.
     */
    public function isDebug(): bool
    {
        return (bool) $this->debug;
    }

    /**
     * Returns build options.
     */
    public function getBuildOptions(): array
    {
        return $this->options;
    }

    /**
     * Set collected pages files.
     */
    public function setPagesFiles(Finder $content): void
    {
        $this->content = $content;
    }

    /**
     * Returns pages files.
     */
    public function getPagesFiles(): ?Finder
    {
        return $this->content;
    }

    /**
     * Set collected data.
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * Returns data collection.
     */
    public function getData(?string $language = null): array
    {
        if ($language) {
            if (empty($this->data[$language])) {
                // fallback to default language
                return $this->data[$this->getConfig()->getLanguageDefault()];
            }

            return $this->data[$language];
        }

        return $this->data;
    }

    /**
     * Set collected static files.
     */
    public function setStatic(array $static): void
    {
        $this->static = $static;
    }

    /**
     * Returns static files collection.
     */
    public function getStatic(): array
    {
        return $this->static;
    }

    /**
     * Set/update Pages collection.
     */
    public function setPages(PagesCollection $pages): void
    {
        $this->pages = $pages;
    }

    /**
     * Returns pages collection.
     */
    public function getPages(): ?PagesCollection
    {
        return $this->pages;
    }

    /**
     * Set assets path list.
     */
    public function setAssets(array $assets): void
    {
        $this->assets = $assets;
    }

    /**
     * Add an asset path to assets list.
     */
    public function addAsset(string $path): void
    {
        if (!\in_array($path, $this->assets, true)) {
            $this->assets[] = $path;
        }
    }

    /**
     * Returns list of assets path.
     */
    public function getAssets(): array
    {
        return $this->assets;
    }

    /**
     * Set menus collection.
     */
    public function setMenus(array $menus): void
    {
        $this->menus = $menus;
    }

    /**
     * Returns all menus, for a language.
     */
    public function getMenus(string $language): Collection\Menu\Collection
    {
        return $this->menus[$language];
    }

    /**
     * Set taxonomies collection.
     */
    public function setTaxonomies(array $taxonomies): void
    {
        $this->taxonomies = $taxonomies;
    }

    /**
     * Returns taxonomies collection, for a language.
     */
    public function getTaxonomies(string $language): ?Collection\Taxonomy\Collection
    {
        return $this->taxonomies[$language];
    }

    /**
     * Set renderer object.
     */
    public function setRenderer(Renderer\RendererInterface $renderer): void
    {
        $this->renderer = $renderer;
    }

    /**
     * Returns Renderer object.
     */
    public function getRenderer(): Renderer\RendererInterface
    {
        return $this->renderer;
    }

    /**
     * Returns metrics array.
     */
    public function getMetrics(): array
    {
        return $this->metrics;
    }

    /**
     * Returns application version.
     *
     * @throws RuntimeException
     */
    public static function getVersion(): string
    {
        if (!isset(self::$version)) {
            try {
                $filePath = Util\File::getRealPath('VERSION');
                $version = Util\File::fileGetContents($filePath);
                if ($version === false) {
                    throw new RuntimeException(\sprintf('Can\'t read content of "%s".', $filePath));
                }
                self::$version = trim($version);
            } catch (\Exception) {
                self::$version = self::VERSION;
            }
        }

        return self::$version;
    }

    /**
     * Log soft errors.
     */
    protected function checkErrors(): void
    {
        // baseurl is required in production
        if (empty(trim((string) $this->getConfig()->get('baseurl'), '/'))) {
            $this->getLogger()->error('`baseurl` configuration key is required in production.');
        }
    }
}

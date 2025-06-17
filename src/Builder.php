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
     * @var array Steps processed by build().
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

    /** @var Config Configuration. */
    protected $config;

    /** @var LoggerInterface Logger. */
    protected $logger;

    /** @var bool Debug mode. */
    protected $debug = false;

    /** @var array Build options. */
    protected $options = [];

    /** @var Finder Content iterator. */
    protected $content;

    /** @var array Data collection. */
    protected $data = [];

    /** @var array Static files collection. */
    protected $static = [];

    /** @var PagesCollection Pages collection. */
    protected $pages;

    /** @var array Assets path collection */
    protected $assets = [];

    /** @var array Menus collection. */
    protected $menus;

    /** @var array Taxonomies collection. */
    protected $taxonomies;

    /** @var Renderer\RendererInterface Renderer. */
    protected $renderer;

    /** @var GeneratorManager Generators manager. */
    protected $generatorManager;

    /** @var string Application version. */
    protected static $version;

    /** @var array Build metrics. */
    protected $metrics = [];

    /** @var string curent build ID */
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
     */
    public function build(array $options): self
    {
        // set start script time and memory usage
        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        // checks soft errors
        $this->checkErrors();

        // prepare options
        $this->options = array_merge([
            'drafts'  => false, // if true, build drafts too
            'dry-run' => false, // if true, generated files are not saved
            'page'    => '',    // if specified, only this page is processed
        ], $options);

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
        // ...and process!
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
    public function getBuilId(): string
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
        foreach ((array) $this->config->get('theme') as $theme) {
            $this->config->import(Config::loadFile(Util::joinFile($this->config->getThemesPath(), $theme, 'config.yml'), true), Config::PRESERVE);
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
                return $this->data[$this->config->getLanguageDefault()];
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
        if (empty(trim((string) $this->config->get('baseurl'), '/'))) {
            $this->getLogger()->error('`baseurl` configuration key is required in production.');
        }
    }
}

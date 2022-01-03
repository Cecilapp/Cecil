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

use Cecil\Collection\Page\Collection as PagesCollection;
use Cecil\Exception\RuntimeException;
use Cecil\Generator\GeneratorManager;
use Cecil\Logger\PrintLogger;
use Cecil\Util\Plateform;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\Finder;

/**
 * Class Builder.
 */
class Builder implements LoggerAwareInterface
{
    const VERSION = '5.x-dev';
    const VERBOSITY_QUIET = -1;
    const VERBOSITY_NORMAL = 0;
    const VERBOSITY_VERBOSE = 1;
    const VERBOSITY_DEBUG = 2;

    /**
     * @var array Steps processed by build().
     */
    protected $steps = [
        'Cecil\Step\Themes\Import',
        'Cecil\Step\Content\Load',
        'Cecil\Step\Content\DataLoad',
        'Cecil\Step\StaticFiles\Load',
        'Cecil\Step\Pages\Create',
        'Cecil\Step\Pages\Convert',
        'Cecil\Step\Taxonomies\Create',
        'Cecil\Step\Pages\Generate',
        'Cecil\Step\Menus\Create',
        'Cecil\Step\StaticFiles\Copy',
        'Cecil\Step\Pages\Render',
        'Cecil\Step\Pages\Save',
        'Cecil\Step\PostProcess\Html',
        'Cecil\Step\PostProcess\Css',
        'Cecil\Step\PostProcess\Js',
        'Cecil\Step\PostProcess\Images',
    ];

    /** @var Config Configuration. */
    protected $config;

    /** @var LoggerInterface Logger. */
    protected $logger;

    /** @var bool Debug mode. */
    protected $debug = false;

    /** @var array Build options. */
    protected $options;

    /** @var Finder Content iterator. */
    protected $content;

    /** @var array Data collection. */
    protected $data = [];

    /** @var array Static files collection. */
    protected $static = [];

    /** @var PagesCollection Pages collection. */
    protected $pages;

    /** @var array Menus collection. */
    protected $menus;

    /** @var Collection\Taxonomy\Collection Taxonomies collection. */
    protected $taxonomies;

    /** @var Renderer\RendererInterface Renderer. */
    protected $renderer;

    /** @var GeneratorManager Generators manager. */
    protected $generatorManager;

    /** @var string Application version. */
    protected static $version;

    /**
     * @param Config|array|null    $config
     * @param LoggerInterface|null $logger
     */
    public function __construct($config = null, LoggerInterface $logger = null)
    {
        $this->setConfig($config)->setSourceDir(null)->setDestinationDir(null);

        // default logger
        if ($logger === null) {
            $logger = new PrintLogger(self::VERBOSITY_VERBOSE);
        }
        $this->setLogger($logger);

        // debug mode?
        if (getenv('CECIL_DEBUG') == 'true' || (bool) $this->getConfig()->get('debug')) {
            $this->debug = true;
        }
    }

    /**
     * Creates a new Builder instance.
     */
    public static function create(): self
    {
        $class = new \ReflectionClass(get_called_class());

        return $class->newInstanceArgs(func_get_args());
    }

    /**
     * Builds a new website.
     */
    public function build(array $options): self
    {
        // set start script time
        $startTime = microtime(true);
        // prepare options
        $this->options = array_merge([
            'drafts'  => false, // build drafts or not
            'dry-run' => false, // if dry-run is true, generated files are not saved
        ], $options);

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
        $stepsTotal = count($steps);
        foreach ($steps as $step) {
            $stepNumber++;
            /** @var Step\StepInterface $step */
            $this->getLogger()->notice($step->getName(), ['step' => [$stepNumber, $stepsTotal]]);
            $step->process();
        }

        // process duration
        $message = sprintf('Built in %ss', round(microtime(true) - $startTime, 2));
        $this->getLogger()->notice($message);

        return $this;
    }

    /**
     * Set configuration.
     *
     * @param Config|array|null $config
     */
    public function setConfig($config): self
    {
        if (!$config instanceof Config) {
            $config = new Config($config);
        }
        if ($this->config !== $config) {
            $this->config = $config;
        }

        return $this;
    }

    /**
     * Returns configuration.
     */
    public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * Config::setSourceDir() alias.
     */
    public function setSourceDir(string $sourceDir = null): self
    {
        $this->config->setSourceDir($sourceDir);

        return $this;
    }

    /**
     * Config::setDestinationDir() alias.
     */
    public function setDestinationDir(string $destinationDir = null): self
    {
        $this->config->setDestinationDir($destinationDir);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setLogger(LoggerInterface $logger)
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
        return $this->debug;
    }

    /**
     * Returns build options.
     */
    public function getBuildOptions(): array
    {
        return $this->options;
    }

    /**
     * Set collected content.
     */
    public function setContent(Finder $content): void
    {
        $this->content = $content;
    }

    /**
     * Returns content.
     */
    public function getContent(): ?Finder
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
    public function getData(): array
    {
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
    public function getPages(): PagesCollection
    {
        return $this->pages;
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
    public function setTaxonomies(Collection\Taxonomy\Collection $taxonomies): void
    {
        $this->taxonomies = $taxonomies;
    }

    /**
     * Returns taxonomies collection.
     */
    public function getTaxonomies(): ?Collection\Taxonomy\Collection
    {
        return $this->taxonomies;
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
     * Returns application version.
     *
     * @throws RuntimeException
     */
    public static function getVersion(): string
    {
        if (!isset(self::$version)) {
            $filePath = __DIR__.'/../VERSION';
            if (Plateform::isPhar()) {
                $filePath = Plateform::getPharPath().'/VERSION';
            }

            try {
                if (!file_exists($filePath)) {
                    throw new RuntimeException(\sprintf('%s file doesn\'t exist!', $filePath));
                }
                $version = Util\File::fileGetContents($filePath);
                if ($version === false) {
                    throw new RuntimeException(\sprintf('Can\'t get %s file!', $filePath));
                }
                self::$version = trim($version);
            } catch (\Exception $e) {
                self::$version = self::VERSION;
            }
        }

        return self::$version;
    }
}

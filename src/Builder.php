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
     *
     * @see build()
     */
    protected $steps = [
        'Cecil\Step\ConfigImport',
        'Cecil\Step\ContentLoad',
        'Cecil\Step\DataLoad',
        'Cecil\Step\StaticLoad',
        'Cecil\Step\PagesCreate',
        'Cecil\Step\PagesConvert',
        'Cecil\Step\TaxonomiesCreate',
        'Cecil\Step\PagesGenerate',
        'Cecil\Step\MenusCreate',
        'Cecil\Step\StaticCopy',
        'Cecil\Step\PagesRender',
        'Cecil\Step\PagesSave',
        'Cecil\Step\AssetsCopy',
        'Cecil\Step\PostProcessHtml',
        'Cecil\Step\PostProcessCss',
        'Cecil\Step\PostProcessJs',
        'Cecil\Step\PostProcessImages',
    ];

    /** @var Config Configuration. */
    protected $config;
    /** @var LoggerInterface Logger. */
    protected $logger;
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
    /** @var Collection\Menu\Collection Menus collection. */
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
    }

    /**
     * Creates a new Builder instance.
     *
     * @return Builder
     */
    public static function create(): self
    {
        $class = new \ReflectionClass(get_called_class());

        return $class->newInstanceArgs(func_get_args());
    }

    /**
     * Builds a new website.
     *
     * @param array $options
     *
     * @return self
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
     *
     * @return self
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
     * @return Config
     */
    public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * Config::setSourceDir() alias.
     *
     * @param string|null $sourceDir
     *
     * @return self
     */
    public function setSourceDir(string $sourceDir = null): self
    {
        $this->config->setSourceDir($sourceDir);

        return $this;
    }

    /**
     * Config::setDestinationDir() alias.
     *
     * @param string|null $destinationDir
     *
     * @return self
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
     *
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * @return array $options Returns build options.
     */
    public function getBuildOptions(): array
    {
        return $this->options;
    }

    /**
     * Set collected content.
     *
     * @param Finder $content
     *
     * @return void
     */
    public function setContent(Finder $content): void
    {
        $this->content = $content;
    }

    /**
     * @return Finder|null
     */
    public function getContent(): ?Finder
    {
        return $this->content;
    }

    /**
     * Set collected data.
     *
     * @param array $data
     *
     * @return void
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Set collected static files.
     *
     * @param array $static
     *
     * @return void
     */
    public function setStatic(array $static): void
    {
        $this->static = $static;
    }

    /**
     * @return array Static files collection.
     */
    public function getStatic(): array
    {
        return $this->static;
    }

    /**
     * Set/update Pages colelction.
     *
     * @param PagesCollection $pages
     *
     * @return void
     */
    public function setPages(PagesCollection $pages): void
    {
        $this->pages = $pages;
    }

    /**
     * @return PagesCollection
     */
    public function getPages(): PagesCollection
    {
        return $this->pages;
    }

    /**
     * @param Collection\Menu\Collection $menus
     *
     * @return void
     */
    public function setMenus(Collection\Menu\Collection $menus): void
    {
        $this->menus = $menus;
    }

    /**
     * @return Collection\Menu\Collection
     */
    public function getMenus(): Collection\Menu\Collection
    {
        return $this->menus;
    }

    /**
     * Set taxonomies collection.
     *
     * @param Collection\Taxonomy\Collection $taxonomies
     *
     * @return void
     */
    public function setTaxonomies(Collection\Taxonomy\Collection $taxonomies): void
    {
        $this->taxonomies = $taxonomies;
    }

    /**
     * @return Collection\Taxonomy\Collection|null
     */
    public function getTaxonomies(): ?Collection\Taxonomy\Collection
    {
        return $this->taxonomies;
    }

    /**
     * Set renderer object.
     *
     * @param Renderer\RendererInterface $renderer
     *
     * @return void
     */
    public function setRenderer(Renderer\RendererInterface $renderer): void
    {
        $this->renderer = $renderer;
    }

    /**
     * @return Renderer\RendererInterface
     */
    public function getRenderer(): Renderer\RendererInterface
    {
        return $this->renderer;
    }

    /**
     * Returns application version.
     *
     * See VERSION file at root.
     *
     * @return string
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
                    throw new \Exception(sprintf('%s file doesn\'t exist!', $filePath));
                }
                $version = Util::fileGetContents($filePath);
                if ($version === false) {
                    throw new \Exception(sprintf('Can\'t get %s file!', $filePath));
                }
                self::$version = trim($version);
            } catch (\Exception $e) {
                self::$version = self::VERSION;
            }
        }

        return self::$version;
    }
}

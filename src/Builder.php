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
    public const VERSION = '7.x-dev';
    public const VERBOSITY_QUIET = -1;
    public const VERBOSITY_NORMAL = 0;
    public const VERBOSITY_VERBOSE = 1;
    public const VERBOSITY_DEBUG = 2;

    /**
     * @var array Steps processed by build().
     */
    protected $steps = [
        'Cecil\Step\Themes\Import',
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

    /** @var array Taxonomies collection. */
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
        // set logger
        if ($logger === null) {
            $logger = new PrintLogger(self::VERBOSITY_VERBOSE);
        }
        $this->setLogger($logger);
        // set config
        $this->setConfig($config)->setSourceDir(null)->setDestinationDir(null);
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

        // checks the configuration
        $this->validConfig();

        // prepare options
        $this->options = array_merge([
            'drafts'  => false, // build drafts or not
            'dry-run' => false, // if dry-run is true, generated files are not saved
            'page'    => '',    // specific page to build
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
        $stepsTotal = \count($steps);
        foreach ($steps as $step) {
            $stepNumber++;
            /** @var Step\StepInterface $step */
            $this->getLogger()->notice($step->getName(), ['step' => [$stepNumber, $stepsTotal]]);
            $stepStartTime = microtime(true);
            $stepStartMemory = memory_get_usage();
            $step->process();
            $this->getLogger()->info(sprintf('%s done in %s (%s)', $step->getName(), Util::convertMicrotime((float) $stepStartTime), Util::convertMemory(memory_get_usage() - $stepStartMemory)));
        }

        // process duration
        $this->getLogger()->notice(sprintf('Built in %s (%s)', Util::convertMicrotime($startTime), Util::convertMemory(memory_get_usage() - $startMemory)));

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
    public function getPages(): ?PagesCollection
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
                    throw new RuntimeException(sprintf('%s file doesn\'t exist!', $filePath));
                }
                $version = Util\File::fileGetContents($filePath);
                if ($version === false) {
                    throw new RuntimeException(sprintf('Can\'t get %s file!', $filePath));
                }
                self::$version = trim($version);
            } catch (\Exception $e) {
                self::$version = self::VERSION;
            }
        }

        return self::$version;
    }

    /**
     * Checks the configuration.
     */
    protected function validConfig(): void
    {
        // baseurl
        if (empty(trim((string) $this->config->get('baseurl'), '/'))) {
            $this->getLogger()->error('Config: `baseurl` is required in production (e.g.: "baseurl: https://example.com/").');
        }
        // default language
        if (!preg_match('/^'.Config::LANG_CODE_PATTERN.'$/', (string) $this->config->get('language'))) {
            throw new RuntimeException(sprintf('Config: default language code "%s" is not valid (e.g.: "language: fr-FR").', $this->config->get('language')));
        }
        // locales
        foreach ((array) $this->config->get('languages') as $lang) {
            if (!isset($lang['locale'])) {
                throw new RuntimeException('Config: a language locale is not defined.');
            }
            if (!preg_match('/^'.Config::LANG_LOCALE_PATTERN.'$/', $lang['locale'])) {
                throw new RuntimeException(sprintf('Config: the language locale "%s" is not valid (e.g.: "locale: fr_FR").', $lang['locale']));
            }
        }
    }
}

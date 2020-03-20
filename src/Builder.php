<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil;

use Cecil\Collection\Page\Collection as PagesCollection;
use Cecil\Generator\GeneratorManager;
use Cecil\Util\Plateform;
use Symfony\Component\Finder\Finder;

/**
 * Class Builder.
 */
class Builder
{
    const VERSION = '5.x-dev';
    const VERBOSITY_QUIET = -1;
    const VERBOSITY_NORMAL = 0;
    const VERBOSITY_VERBOSE = 1;
    const VERBOSITY_DEBUG = 2;

    /**
     * Steps that are processed by build().
     *
     * @var array
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
        'Cecil\Step\OptimizeCss',
        'Cecil\Step\OptimizeJs',
        'Cecil\Step\OptimizeHtml',
        'Cecil\Step\OptimizeImages',
    ];
    /**
     * App version.
     *
     * @var string
     */
    protected static $version;
    /**
     * Configuration.
     *
     * @var Config
     */
    protected $config;
    /**
     * Content iterator.
     *
     * @var Finder
     */
    protected $content;
    /**
     * Data collection.
     *
     * @var array
     */
    protected $data = [];
    /**
     * Static files collection.
     *
     * @var array
     */
    protected $static = [];
    /**
     * Pages collection.
     *
     * @var PagesCollection
     */
    protected $pages;
    /**
     * Menus collection.
     *
     * @var Collection\Menu\Collection
     */
    protected $menus;
    /**
     * Taxonomies collection.
     *
     * @var Collection\Taxonomy\Collection
     */
    protected $taxonomies;
    /**
     * Renderer (Renderer\Twig).
     *
     * @var Renderer\RendererInterface
     */
    protected $renderer;
    /**
     * Message callback.
     *
     * @var \Closure
     */
    protected $messageCallback;
    /**
     * Generators manager.
     *
     * @var GeneratorManager
     */
    protected $generatorManager;
    /**
     * Log.
     *
     * @var array
     */
    protected $log;
    /**
     * Options.
     *
     * @var array
     */
    protected $options;

    /**
     * Builder constructor.
     *
     * @param Config|array|null $config
     * @param \Closure|null     $messageCallback
     */
    public function __construct($config = null, \Closure $messageCallback = null)
    {
        $this->setConfig($config)
            ->setSourceDir(null)
            ->setDestinationDir(null);
        $this->setMessageCallback($messageCallback);
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
     * Set config.
     *
     * @param Config|array|null $config
     *
     * @return $this
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
     * Config::setSourceDir alias.
     *
     * @param string|null $sourceDir
     *
     * @return $this
     */
    public function setSourceDir(string $sourceDir = null): self
    {
        $this->config->setSourceDir($sourceDir);

        return $this;
    }

    /**
     * Config::setDestinationDir alias.
     *
     * @param string|null $destinationDir
     *
     * @return $this
     */
    public function setDestinationDir(string $destinationDir = null): self
    {
        $this->config->setDestinationDir($destinationDir);

        return $this;
    }

    /**
     * @param Finder $content
     */
    public function setContent(Finder $content)
    {
        $this->content = $content;
    }

    /**
     * @return Finder
     */
    public function getContent(): Finder
    {
        return $this->content;
    }

    /**
     * @param array $data
     */
    public function setData(array $data)
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
     * @param array $static
     */
    public function setStatic(array $static)
    {
        $this->static = $static;
    }

    /**
     * @return array static files collection
     */
    public function getStatic(): array
    {
        return $this->static;
    }

    /**
     * @param PagesCollection $pages
     */
    public function setPages(PagesCollection $pages)
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
     */
    public function setMenus(Collection\Menu\Collection $menus)
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
     * @param Collection\Taxonomy\Collection $taxonomies
     */
    public function setTaxonomies(Collection\Taxonomy\Collection $taxonomies)
    {
        $this->taxonomies = $taxonomies;
    }

    /**
     * @return Collection\Taxonomy\Collection
     */
    public function getTaxonomies(): Collection\Taxonomy\Collection
    {
        return $this->taxonomies;
    }

    /**
     * @param \Closure|null $messageCallback
     */
    public function setMessageCallback(\Closure $messageCallback = null)
    {
        if ($messageCallback === null) {
            $messageCallback = function ($code, $message = '', $itemsCount = 0, $itemsMax = 0) {
                switch ($code) {
                    case 'CONFIG':
                    case 'LOCATE':
                    case 'DATA':
                    case 'CREATE':
                    case 'CONVERT':
                    case 'GENERATE':
                    case 'MENU':
                    case 'COPY':
                    case 'OPTIMIZE':
                    case 'RENDER':
                    case 'SAVE':
                    case 'TIME':
                        $log = sprintf("%s\n", $message);
                        $this->addLog($log);
                        break;
                    case 'CONFIG_PROGRESS':
                    case 'LOCATE_PROGRESS':
                    case 'DATA_PROGRESS':
                    case 'CREATE_PROGRESS':
                    case 'CONVERT_PROGRESS':
                    case 'GENERATE_PROGRESS':
                    case 'MENU_PROGRESS':
                    case 'COPY_PROGRESS':
                    case 'OPTIMIZE_PROGRESS':
                    case 'RENDER_PROGRESS':
                    case 'SAVE_PROGRESS':
                        if ($itemsCount > 0) {
                            $log = sprintf("(%u/%u) %s\n", $itemsCount, $itemsMax, $message);
                            $this->addLog($log, 1);
                        } else {
                            $log = sprintf("%s\n", $message);
                            $this->addLog($log, 1);
                        }
                        break;
                    case 'CONFIG_ERROR':
                    case 'LOCATE_ERROR':
                    case 'DATA_ERROR':
                    case 'CREATE_ERROR':
                    case 'CONVERT_ERROR':
                    case 'GENERATE_ERROR':
                    case 'MENU_ERROR':
                    case 'COPY_ERROR':
                    case 'OPTIMIZE_ERROR':
                    case 'RENDER_ERROR':
                    case 'SAVE_ERROR':
                        $log = sprintf(">> %s\n", $message);
                        $this->addLog($log);
                        break;
                }
            };
        }
        $this->messageCallback = $messageCallback;
    }

    /**
     * @return \Closure
     */
    public function getMessageCb(): \Closure
    {
        return $this->messageCallback;
    }

    /**
     * @param Renderer\RendererInterface $renderer
     */
    public function setRenderer(Renderer\RendererInterface $renderer)
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
     * @param string $log  Message
     * @param int    $type Verbosity
     *
     * @return array|null
     */
    public function addLog(string $log, int $type = 0): ?array
    {
        $this->log[] = [
            'type' => $type,
            'log'  => $log,
        ];

        return $this->getLog($type);
    }

    /**
     * @param int $type
     *
     * @return array|null
     */
    public function getLog(int $type = 0): ?array
    {
        if (is_array($this->log)) {
            return array_filter($this->log, function ($key) use ($type) {
                return $key['type'] <= $type;
            });
        }
    }

    /**
     * @param int $type
     *
     * Display $log string.
     */
    public function showLog(int $type = 0)
    {
        if ($log = $this->getLog($type)) {
            foreach ($log as $value) {
                printf('%s', $value['log']);
            }
        }
    }

    /**
     * @return array $options
     */
    public function getBuildOptions(): array
    {
        return $this->options;
    }

    /**
     * Builds a new website.
     *
     * @param array $options
     *
     * @return $this
     */
    public function build(array $options): self
    {
        // start script time
        $startTime = microtime(true);
        $this->options = array_merge([
            'verbosity' => self::VERBOSITY_NORMAL, // -1: quiet, 0: normal, 1: verbose, 2: debug
            'drafts'    => false, // build drafts or not
            'dry-run'   => false, // if dry-run is true, generated files are not saved
        ], $options);

        $steps = [];
        // init...
        foreach ($this->steps as $step) {
            /* @var $stepClass Step\StepInterface */
            $stepClass = new $step($this);
            $stepClass->init($this->options);
            $steps[] = $stepClass;
        }
        $this->steps = $steps;
        // ... and process!
        foreach ($this->steps as $step) {
            /* @var $step Step\StepInterface */
            $step->runProcess();
        }
        // show process time
        call_user_func_array($this->messageCallback, [
            'TIME',
            sprintf('Built in %ss', round(microtime(true) - $startTime, 2)),
        ]);
        // show log
        $this->showLog($this->options['verbosity']);

        return $this;
    }

    /**
     * Return version.
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
                self::$version = trim(file_get_contents($filePath));
                if (self::$version === false) {
                    throw new \Exception(sprintf('Can\'t get %s file!', $filePath));
                }
            } catch (\Exception $e) {
                self::$version = self::VERSION;
            }
        }

        return self::$version;
    }
}

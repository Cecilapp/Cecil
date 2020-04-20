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
     * @var array Steps that are processed by build().
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
    /** @var string App version. */
    protected static $version;
    /** @var Config Configuration. */
    protected $config;
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
    /** @var \Closure Message callback. */
    protected $messageCallback;
    /** @var GeneratorManager Generators manager. */
    protected $generatorManager;
    /** @var array Log. */
    protected $log;
    /** @var array Options. */
    protected $options;

    /**
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
     * Set log message format by step status (start, progress or error)
     * in a callback function.
     *
     * @param \Closure|null $messageCallback
     *
     * @return void
     */
    public function setMessageCallback(\Closure $messageCallback = null): void
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
                    case 'RENDER':
                    case 'SAVE':
                    case 'POSTPROCESS':
                    case 'TIME':
                    case 'DEFAULT':
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
                    case 'RENDER_PROGRESS':
                    case 'SAVE_PROGRESS':
                    case 'POSTPROCESS_PROGRESS':
                    case 'DEFAULT_PROGRESS':
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
                    case 'POSTPROCESS_ERROR':
                    case 'DEFAULT_ERROR':
                        $log = sprintf(">> %s\n", $message);
                        $this->addLog($log);
                        break;
                }
            };
        }
        $this->messageCallback = $messageCallback;
    }

    /**
     * @return \Closure Return message callback function.
     */
    public function getMessageCb(): \Closure
    {
        return $this->messageCallback;
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
     * Add log entry.
     *
     * @param string $log  Log message.
     * @param int    $type Verbosity level.
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
     * @return array|null Return log array filtered by type.
     */
    public function getLog(int $type = 0): ?array
    {
        if (is_array($this->log)) {
            return array_filter($this->log, function ($key) use ($type) {
                return $key['type'] <= $type;
            });
        }

        return null;
    }

    /**
     * Print log message.
     *
     * @param int $type
     *
     * @return void
     */
    public function showLog(int $type = 0): void
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
     * @return self
     */
    public function build(array $options): self
    {
        // set start script time
        $startTime = microtime(true);
        // prepare options
        $this->options = array_merge([
            'verbosity' => self::VERBOSITY_NORMAL, // -1: quiet, 0: normal, 1: verbose, 2: debug
            'drafts'    => false, // build drafts or not
            'dry-run'   => false, // if dry-run is true, generated files are not saved
        ], $options);

        // process each step
        $steps = [];
        // init...
        foreach ($this->steps as $step) {
            /** @var Step\StepInterface $stepClass */
            $stepClass = new $step($this);
            $stepClass->init($this->options);
            $steps[] = $stepClass;
        }
        $this->steps = $steps;
        // ... and process!
        foreach ($this->steps as $step) {
            /** @var Step\StepInterface $step */
            $step->runProcess();
        }

        // add process duration to log
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
                self::$version = trim(Util::fileGetContents($filePath));
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

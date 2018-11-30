<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole;

use PHPoole\Collection\Collection as PageCollection;
use PHPoole\Generator\GeneratorManager;
use Symfony\Component\Finder\Finder;

/**
 * Class Builder.
 */
class Builder
{
    const VERSION = '2.x-dev';
    const VERBOSITY_QUIET = -1;
    const VERBOSITY_NORMAL = 0;
    const VERBOSITY_VERBOSE = 1;
    const VERBOSITY_DEBUG = 2;

    /**
     * Library version.
     *
     * @var string
     */
    protected $version;
    /**
     * Steps that are processed by build().
     *
     * @var array
     *
     * @see build()
     */
    protected $steps = [
        'PHPoole\Step\ImportConfig',
        'PHPoole\Step\LocateContent',
        'PHPoole\Step\CreatePages',
        'PHPoole\Step\ConvertPages',
        'PHPoole\Step\GeneratePages',
        'PHPoole\Step\GenerateMenus',
        'PHPoole\Step\CopyStatic',
        'PHPoole\Step\RenderPages',
        'PHPoole\Step\SavePages',
    ];
    /**
     * Config.
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
     * Pages collection.
     *
     * @var PageCollection
     */
    protected $pages;
    /**
     * Collection of site menus.
     *
     * @var Collection\Menu\Collection
     */
    protected $menus;
    /**
     * Twig renderer.
     *
     * @var Renderer\Twig
     */
    protected $renderer;
    /**
     * @var \Closure
     */
    protected $messageCallback;
    /**
     * @var GeneratorManager
     */
    protected $generatorManager;
    /**
     * @var array
     */
    protected $log;
    /**
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
    public static function create()
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
    public function setConfig($config)
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
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Config::setSourceDir alias.
     *
     * @param $sourceDir
     *
     * @return $this
     */
    public function setSourceDir($sourceDir)
    {
        $this->config->setSourceDir($sourceDir);

        return $this;
    }

    /**
     * Config::setDestinationDir alias.
     *
     * @param $destinationDir
     *
     * @return $this
     */
    public function setDestinationDir($destinationDir)
    {
        $this->config->setDestinationDir($destinationDir);

        return $this;
    }

    /**
     * @param $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * @return Finder
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param $pages
     */
    public function setPages($pages)
    {
        $this->pages = $pages;
    }

    /**
     * @return PageCollection
     */
    public function getPages()
    {
        return $this->pages;
    }

    /**
     * @param $menus
     */
    public function setMenus($menus)
    {
        $this->menus = $menus;
    }

    /**
     * @return Collection\Menu\Collection
     */
    public function getMenus()
    {
        return $this->menus;
    }

    /**
     * @param \Closure|null $messageCallback
     */
    public function setMessageCallback($messageCallback = null)
    {
        if ($messageCallback === null) {
            $messageCallback = function ($code, $message = '', $itemsCount = 0, $itemsMax = 0) {
                switch ($code) {
                    case 'CONFIG':
                    case 'LOCATE':
                    case 'CREATE':
                    case 'CONVERT':
                    case 'GENERATE':
                    case 'MENU':
                    case 'COPY':
                    case 'RENDER':
                    case 'SAVE':
                    case 'TIME':
                        $log = sprintf("%s\n", $message);
                        $this->addLog($log);
                        break;
                    case 'CONFIG_PROGRESS':
                    case 'LOCATE_PROGRESS':
                    case 'CREATE_PROGRESS':
                    case 'CONVERT_PROGRESS':
                    case 'GENERATE_PROGRESS':
                    case 'MENU_PROGRESS':
                    case 'COPY_PROGRESS':
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
                    case 'LOCATE_ERROR':
                    case 'CREATE_ERROR':
                    case 'CONVERT_ERROR':
                    case 'GENERATE_ERROR':
                    case 'MENU_ERROR':
                    case 'COPY_ERROR':
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
    public function getMessageCb()
    {
        return $this->messageCallback;
    }

    /**
     * @param $renderer
     */
    public function setRenderer($renderer)
    {
        $this->renderer = $renderer;
    }

    /**
     * @return Renderer\Twig
     */
    public function getRenderer()
    {
        return $this->renderer;
    }

    /**
     * @param string $log
     * @param int    $type
     *
     * @return array|null
     */
    public function addLog($log, $type = 0)
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
    public function getLog($type = 0)
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
    public function showLog($type = 0)
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
    public function getBuildOptions()
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
    public function build($options)
    {
        // backward compatibility
        if ($options === true) {
            $options['verbosity'] = self::VERBOSITY_VERBOSE;
        }
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
            sprintf('Built in %ss', round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 2)),
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
    public function getVersion()
    {
        if (!isset($this->version)) {
            try {
                $this->version = @file_get_contents(__DIR__.'/../VERSION');
                if ($this->version === false) {
                    throw new \Exception('Can\'t get version file!');
                }
            } catch (\Exception $e) {
                $this->version = self::VERSION;
            }
        }

        return $this->version;
    }
}

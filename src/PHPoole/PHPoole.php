<?php
/**
 * PHPoole is a light and easy static website generator written in PHP.
 *
 * @see http://phpoole.narno.org
 *
 * @author Arnaud Ligny <arnaud@ligny.org>
 * @license The MIT License (MIT)
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 */
namespace PHPoole;

use Exception;
use ParsedownExtra;
use Zend\EventManager\EventManager;
use Zend\Log\Logger as Logger;
use Zend\Log\Writer\Stream as LogWriter;

define('DS', DIRECTORY_SEPARATOR);

/**
 * PHPoole.
 */
class PHPoole
{
    const VERSION = '2.0.0-dev';
    const URL = 'http://phpoole.narno.org';
    const CONFIG_FILENAME = 'config.ini';
    const CONTENT_DIRNAME = 'content';
    const LAYOUTS_DIRNAME = 'layouts';
    const STATIC_DIRNAME = 'static';
    const PLUGINS_DIRNAME = 'plugins';
    const SITE_SRV_DIRNAME = 'site';
    const SITE_DEP_DIRNAME = 'deploy';
    const LOG_FILENAME = 'phpoole.log';

    protected $_websitePath;
    protected $_websiteFileInfo;
    protected $_events;
    protected $_config = null;
    protected $_messages = [];
    protected $_pages = [];
    protected $_menu = [];
    protected $_processor;
    protected $_localServe = false;

    /**
     * @param $websitePath
     *
     * @throws Exception
     */
    public function __construct($websitePath)
    {
        $splFileInfo = new \SplFileInfo($websitePath);
        if (!$splFileInfo->isDir()) {
            throw new Exception('Invalid directory provided');
        } else {
            $this->_websiteFileInfo = $splFileInfo;
            $this->_websitePath = $splFileInfo->getRealPath();
        }
        // Load plugins
        $this->_events = new EventManager();
        $this->loadPlugins();
    }

    /**
     * @return \SplFileInfo
     */
    public function getWebsiteFileInfo()
    {
        return $this->_websiteFileInfo;
    }

    /**
     * @param $path
     *
     * @return string
     */
    public function setWebsitePath($path)
    {
        $this->_websitePath = $path;

        return $this->getWebsitePath();
    }

    /**
     * @return string
     */
    public function getWebsitePath()
    {
        return $this->_websitePath;
    }

    /**
     * Get config from config.ini file.
     *
     * @param string $key
     *
     * @throws Exception
     *
     * @return array|null
     */
    public function getConfig($key = '')
    {
        if ($this->_config == null) {
            $configFilePath = $this->getWebsitePath().'/'.self::CONFIG_FILENAME;
            if (!file_exists($configFilePath)) {
                throw new Exception('Cannot get config file');
            }
            if (!($this->_config = parse_ini_file($configFilePath, true))) {
                throw new Exception('Cannot parse config file');
            }
            if (!empty($key)) {
                if (!array_key_exists($key, $this->_config)) {
                    throw new Exception(sprintf('Cannot find %s key in config file', $key));
                }

                return $this->_config[$key];
            }
        }

        return $this->_config;
    }

    /**
     * @param $msg
     */
    private function debug($msg)
    {
        $writer = new LogWriter($this->getWebsitePath().'/'.self::LOG_FILENAME);
        $logger = new Logger();
        $logger->addWriter($writer);
        $logger->debug($msg);
    }

    /**
     * @return EventManager
     */
    public function getEvents()
    {
        return $this->_events;
    }

    /**
     * @return array
     */
    public function getMessages()
    {
        return $this->_messages;
    }

    /**
     * @param $message
     * @param bool $verbose
     *
     * @return array
     */
    public function addMessage($message, $verbose = false)
    {
        // @todo create a cleaner solution...
        if (!$verbose) {
            $this->_messages[] = $message;
        }

        return $this->getMessages();
    }

    /**
     * @return array
     */
    public function clearMessages()
    {
        $tmpMessages = $this->_messages;
        $this->_messages = [];

        return $tmpMessages;
    }

    /**
     * @param string $subDir
     *
     * @return array
     */
    public function getPages($subDir = '')
    {
        if (!empty($subDir)) {
            foreach ($this->_pages as $key => $value) {
                if (strstr($key, $subDir.'/') !== false) {
                    $tmpPages[] = $this->_pages[$key];
                }
            }

            return $tmpPages;
        }

        return $this->_pages;
    }

    /**
     * @param $index
     * @param $data
     *
     * @return array
     */
    public function addPage($index, $data)
    {
        $this->_pages[$index] = $data;

        return $this->getPages();
    }

    /**
     * @param string $menu
     *
     * @return array
     */
    public function getMenu($menu = '')
    {
        if (!empty($menu) && array_key_exists($menu, $this->_menu)) {
            return $this->_menu[$menu];
        }

        return $this->_menu;
    }

    /**
     * @param $menu
     * @param $entry
     *
     * @return array
     */
    public function addMenuEntry($menu, $entry)
    {
        $this->_menu[$menu][] = $entry;

        return $this->getMenu($menu);
    }

    /**
     * Temporary method to prepare (sort) "nav" menu.
     *
     * @return array
     */
    public function prepareMenuNav()
    {
        $menuNav = $this->getMenu('nav');
        // sort nav menu
        foreach ($menuNav as $key => $row) {
            $path[$key] = $row['path'];
        }
        if (isset($path) && is_array($path)) {
            array_multisort($path, SORT_ASC, $menuNav);
        }

        return $menuNav;
    }

    /**
     * @param bool $status
     *
     * @return bool
     */
    public function setLocalServe($status = true)
    {
        return $this->_localServe = ($status) ? true : false;
    }

    /**
     * @return bool
     */
    public function isLocalServe()
    {
        return $this->_localServe;
    }

    /**
     * Initialization of a new PHPoole website.
     *
     * @param bool $force
     *
     * @throws Exception
     *
     * @return array
     */
    public function init($force = false)
    {
        $this->triggerEvent(__FUNCTION__, func_get_args(), 'pre');

        if (file_exists($this->getWebsitePath().'/'.self::CONFIG_FILENAME)) {
            if (!$force === true) {
                throw new Exception('The website is already initialized');
            }
        }
        $this->addMessage(Skeleton::createConfigFile($this));
        $this->addMessage(Skeleton::createContentDir($this));
        $this->addMessage(Skeleton::createContentDefaultFile($this));
        $this->addMessage(Skeleton::createLayoutsDir($this));
        $this->addMessage(Skeleton::createLayoutDefaultFile($this));
        $this->addMessage(Skeleton::createLayoutWatchFile($this));
        $this->addMessage(Skeleton::createStaticDir($this));
        $this->addMessage(Skeleton::createReadmeFile($this));
        $this->addMessage(Skeleton::createRouterFile($this));

        $this->triggerEvent(__FUNCTION__, func_get_args(), 'post');

        return $this->getMessages();
    }

    /**
     * Is a valid PHPoole website.
     *
     * @return bool
     */
    public function isPhpoole()
    {
        try {
            $this->getConfig();

            return $this;
        } catch (\Exception $e) {
            throw new Exception('The website is not yet initialized');
        }
    }

    /**
     * Load pages files from content/pages.
     *
     * @throws Exception
     *
     * @return $this
     */
    public function loadPages()
    {
        // iterate pages files, filtered by markdown "md" extension
        $pagesIterator = new Spl\FileIterator(
            $this->getWebsitePath().'/'.self::CONTENT_DIRNAME,
            'md'
        );
        foreach ($pagesIterator as $filePage) {
            // loading front-matter's informations
            $pageInfo = $filePage->parse()->getData('info');
            // creating an index (id as string)
            $pageIndex = ($pagesIterator->getSubPath() ? $pagesIterator->getSubPath() : 'home');
            // agglomerating page's datas
            $pageData = $pageInfo;
            $pageData['title'] = (
                isset($pageInfo['title']) && !empty($pageInfo['title'])
                ? $pageInfo['title']
                : ucfirst($filePage->getBasename('.md'))
            );
            $pageData['path'] = str_replace(DS, '/', $pagesIterator->getSubPath());
            $pageData['basename'] = $filePage->getBasename('.md').'.html';
            $pageData['layout'] = (
                isset($pageInfo['layout'])
                    && is_file($this->getWebsitePath().'/'.self::LAYOUTS_DIRNAME.'/'.(isset($this->getConfig()['site']['layouts']) ? $this->getConfig()['site']['layouts'].'/' : '').$pageInfo['layout'].'.html')
                ? $pageInfo['layout'].'.html'
                : 'default.html'
            );
            // in case of external content
            if (isset($pageInfo['content']) /* && is valid URL to md file */) {
                if (false === ($pageContent = @file_get_contents($pageInfo['content'], false))) {
                    throw new Exception(sprintf("Cannot get contents from %s\n", $filePage->getFilename()));
                }
            // or local content
            } else {
                $pageContent = $filePage->getData('content_raw');
            }
            // content processing
            $pageData['content'] = $this->process($pageContent);

            // event postloop
            $results = $this->triggerEvent(__FUNCTION__, [
                'pageInfo'  => $pageInfo,
                'pageIndex' => $pageIndex,
                'pageData'  => $pageData,
            ], 'postloop');
            if ($results) {
                extract($results);
            }

            // adding page processed page
            $this->addPage($pageIndex, $pageData);

            // adding menu entry?
            if (isset($pageInfo['menu'])) { // "nav" for example
                $menuEntry = (
                    !empty($pageInfo['menu'])
                    ? [
                        'title' => $pageInfo['title'],
                        'path'  => str_replace(DS, '/', $pagesIterator->getSubPath()),
                    ]
                    : ''
                );
                $this->addMenuEntry($pageInfo['menu'], $menuEntry);
            }

            // unset datas for the next loop
            unset($pageInfo);
            unset($pageIndex);
            unset($pageData);
        }

        return $this;
    }

    /**
     * Process Markdown content.
     *
     * @param $rawContent
     *
     * @throws Exception
     *
     * @return string
     */
    public function process($rawContent)
    {
        $this->_processor = new ParsedownExtra();

        return $this->_processor->text($rawContent);
    }

    /**
     * @param $templatesPath
     *
     * @return \Twig_Environment
     */
    public function tplEngine($templatesPath)
    {
        $twigLoader = new \Twig_Loader_Filesystem($templatesPath);
        $twig = new \Twig_Environment($twigLoader, [
            'autoescape' => false,
            'debug'      => true,
            'cache'      => false,
        ]);
        $twig->addExtension(new \Twig_Extension_Debug());

        return $twig;
    }

    /**
     * Build website.
     *
     * @throws Exception
     *
     * @return array
     */
    public function build()
    {
        // loading templates (layoyts) engine (Twig)
        //if (isset($this->getConfig()['site']['layouts'])) {
        //    $tplEngine = $this->tplEngine($this->getWebsitePath() . '/' . self::LAYOUTS_DIRNAME . '/' . $this->getConfig()['site']['layouts']);
        //} else {
            $tplEngine = $this->tplEngine($this->getWebsitePath().'/'.self::LAYOUTS_DIRNAME);
        //}
        // preparing nav menu
        $menuNav = $this->prepareMenuNav();
        // loading, then rendering pages
        $pagesIterator = (new \ArrayObject($this->getPages()))->getIterator();
        $pagesIterator->ksort();
        while ($pagesIterator->valid()) {
            $page = $pagesIterator->current();
            // template variables
            $pageExtra = [
                'nav' => (isset($menuNav) ? $menuNav : ''),
            ];
            $tplVariables = [
                'phpoole' => [
                    'version' => self::VERSION,
                    'url'     => self::URL,
                ],
                'site' => new Proxy($this),
                'page' => array_merge($page, $pageExtra),
            ];
            // rendering
            if ($this->isLocalServe()) { // move logic in router?
                $rendered = $tplEngine->render('watch.html', array_merge($tplVariables, ['layout_master' => $page['layout']]));
            } else {
                $rendered = $tplEngine->render($page['layout'], $tplVariables);
            }
            //$this->debug(print_r($rendered, true));
            // dir writing
            if (!is_dir($this->getWebsitePath().'/'.self::SITE_SRV_DIRNAME.'/'.$page['path'])) {
                if (!@mkdir($this->getWebsitePath().'/'.self::SITE_SRV_DIRNAME.'/'.$page['path'], 0777, true)) {
                    throw new Exception(sprintf('Cannot create %s', $this->getWebsitePath().'/'.$page['path']));
                }
            }
            // file deleting
            if (is_file($this->getWebsitePath().'/'.self::SITE_SRV_DIRNAME.'/'.($page['path'] != '' ? $page['path'].'/' : '').$page['basename'])) {
                if (!@unlink($this->getWebsitePath().'/'.self::SITE_SRV_DIRNAME.'/'.($page['path'] != '' ? $page['path'].'/' : '').$page['basename'])) {
                    throw new Exception(sprintf('Cannot delete %s%s', ($page['path'] != '' ? $page['path'].'/' : ''), $page['basename']));
                }
                $this->addMessage('Delete '.($page['path'] != '' ? $page['path'].'/' : '').$page['basename'], true);
            }
            // file writing
            if (!@file_put_contents(sprintf('%s%s', $this->getWebsitePath().'/'.self::SITE_SRV_DIRNAME.'/'.($page['path'] != '' ? $page['path'].'/' : ''), $page['basename']), $rendered)) {
                throw new Exception(sprintf('Cannot write %s%s', ($page['path'] != '' ? $page['path'].'/' : ''), $page['basename']));
            }
            $this->addMessage(sprintf('Write %s%s', ($page['path'] != '' ? $page['path'].'/' : ''), $page['basename']), true);

            // event postloop
            $this->triggerEvent(__FUNCTION__, [
                'page' => $page,
            ], 'postloop');
            $pagesIterator->next();
        }
        $this->addMessage('Write pages');
        // copy static
        Util::copy(
            $this->getWebsitePath().'/'.self::STATIC_DIRNAME,
            $this->getWebsitePath().'/'.self::SITE_SRV_DIRNAME
        );
        $this->addMessage('Copy files in static directory');

        return $this->getMessages();
    }

    /**
     * @param $method
     * @param $args
     * @param $when
     *
     * @return $this|mixed
     */
    //public function triggerEvent($method, $args, $when=array('pre','post'))
    public function triggerEvent($method, $args, $when)
    {
        $reflector = new \ReflectionClass(__CLASS__);
        $parameters = $reflector->getMethod($method)->getParameters();
        if (!empty($parameters)) {
            $params = [];
            foreach ($parameters as $parameter) {
                $params[$parameter->getName()] = $parameter->getName();
            }
            $args = array_combine($params, $args);
        }
        $results = $this->getEvents()->trigger($method.'.'.$when, $this, $args);
        if ($results) {
            return $results->last();
        }

        return $this;
    }

    /**
     * Loads plugins in the plugins/ directory if exist.
     */
    private function loadPlugins()
    {
        try {
            $configPlugins = $this->getConfig('plugins');
        } catch (Exception $e) {
            $configPlugins = [];
        }
        $pluginsDirCore = __DIR__.'/'.self::PLUGINS_DIRNAME;
        $pluginsDir = $this->getWebsitePath().'/'.self::PLUGINS_DIRNAME;
        $pluginsIterator = new \AppendIterator();
        if (is_dir($pluginsDirCore)) {
            $pluginsIterator1 = new \FilesystemIterator($pluginsDirCore);
            $pluginsIterator->append($pluginsIterator1);
        }
        if (is_dir($pluginsDir)) {
            $pluginsIterator2 = new \FilesystemIterator($pluginsDir);
            $pluginsIterator->append($pluginsIterator2);
        }
        if (iterator_count($pluginsIterator) > 0) {
            foreach ($pluginsIterator as $plugin) {
                if (array_key_exists($plugin->getBasename(), $configPlugins)
                && $configPlugins[$plugin->getBasename()] == 'disabled') {
                    continue;
                }
                if ($plugin->isDir()) {
                    $pluginName = $plugin->getBasename();
                    $pluginClass = "PHPoole\\$pluginName";
                    include_once $plugin->getPathname().'/Plugin.php';
                    if (class_exists($pluginClass)) {
                        $pluginObject = new $pluginClass($this->getEvents());
                        // init
                        if (method_exists($pluginObject, 'preInit')) {
                            $this->getEvents()->attach('init.pre', [$pluginObject, 'preInit']);
                        }
                        if (method_exists($pluginObject, 'postInit')) {
                            $this->getEvents()->attach('init.post', [$pluginObject, 'postInit']);
                        }
                        // load pages
                        if (method_exists($pluginObject, 'postloopLoadPages')) {
                            $this->getEvents()->attach('loadPages.postloop', [$pluginObject, 'postloopLoadPages']);
                        }
                        // generate
                        if (method_exists($pluginObject, 'postloopGenerate')) {
                            $this->getEvents()->attach('generate.postloop', [$pluginObject, 'postloopGenerate']);
                        }
                    }
                }
            }
        }
    }
}

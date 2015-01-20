<?php
/**
 * PHPoole is a light and easy static website generator written in PHP.
 * @see http://phpoole.narno.org
 *
 * @author Arnaud Ligny <arnaud@ligny.org>
 * @license The MIT License (MIT)
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 */

namespace PHPoole;

use Zend\Console\ColorInterface as Color;
use Zend\EventManager\EventManager;
use Michelf\MarkdownExtra;
use PHPoole\Spl;
use PHPoole\Skeleton;
use Exception;

define('DS', DIRECTORY_SEPARATOR);

/**
 * PHPoole
 */
class PHPoole
{
    const VERSION = '0.0.2';
    const URL = 'http://phpoole.narno.org';
    //
    const PHPOOLE_DIRNAME = '_phpoole';
    const CONFIG_FILENAME = 'config.ini';
    const LAYOUTS_DIRNAME = 'layouts';
    const ASSETS_DIRNAME  = 'assets';
    const CONTENT_DIRNAME = 'content';
    const CONTENT_PAGES_DIRNAME = 'pages';
    const PLUGINS_DIRNAME  = 'plugins';

    protected $_websitePath;
    protected $_websiteFileInfo;
    protected $_events;
    protected $_config = null;
    protected $_messages = array();
    protected $_pages = array();
    protected $_menu = array();
    public $localServe = false;
    protected $_processor;

    /**
     * @param $websitePath
     * @throws Exception
     */
    public function __construct($websitePath)
    {
        $splFileInfo = new \SplFileInfo($websitePath);
        if (!$splFileInfo->isDir()) {
            throw new Exception('Invalid directory provided');
        }
        else {
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
     * @return array
     */
    public function addMessage($message, $verbose=false)
    {
        if (!$verbose) { // temporary
            $this->_messages[] = $message;
        }
        return $this->getMessages();
    }

    /**
     *
     */
    public function clearMessages()
    {
        $this->_messages = array();
    }

    /**
     * @param string $subDir
     * @return array
     */
    public function getPages($subDir='')
    {
        if (!empty($subDir)) {
            foreach ($this->_pages as $key => $value) {
                if (strstr($key, $subDir . '/') !== false) {
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
     * @return array
     */
    public function addPage($index, $data)
    {
        $this->_pages[$index] = $data;
        return $this->getPages();
    }

    /**
     * @param string $menu
     * @return array
     */
    public function getMenu($menu='')
    {
        if (!empty($menu) && array_key_exists($menu, $this->_menu)) {
            return $this->_menu[$menu];
        }
        return $this->_menu;
    }

    /**
     * @param $menu
     * @param $entry
     * @return array
     */
    public function addMenuEntry($menu, $entry)
    {
        $this->_menu[$menu][] = $entry;
        return $this->getMenu($menu);
    }

    /**
     * @param $method
     * @param $args
     * @param $when
     * @return $this|mixed
     */
    //public function triggerEvent($method, $args, $when=array('pre','post'))
    public function triggerEvent($method, $args, $when)
    {
        $reflector = new \ReflectionClass(__CLASS__);
        $parameters = $reflector->getMethod($method)->getParameters();
        if (!empty($parameters)) {
            $params = array();
            foreach ($parameters as $parameter) {
                $params[$parameter->getName()] = $parameter->getName();
            }
            $args = array_combine($params, $args);
        }
        $results = $this->getEvents()->trigger($method . '.' . $when, $this, $args);
        if ($results) {
           return $results->last();
        }
        return $this;
    }

    /**
     * temporary method
     *
     * @param $status
     * @return mixed
     */
    public function setLocalServe($status)
    {
        return $this->localServe = $status;
    }

    /**
     * Initialization of a new PHPoole website
     *
     * @param bool $force
     * @return array
     * @throws Exception
     */
    public function init($force=false)
    {
        $this->triggerEvent(__FUNCTION__, func_get_args(), 'pre');

        if (file_exists($this->getWebsitePath() . '/' . self::PHPOOLE_DIRNAME . '/' . self::CONFIG_FILENAME)) {
            if ($force === true) {
                Util::rmDir($this->getWebsitePath() . '/' . self::PHPOOLE_DIRNAME);
            }
            else {
                throw new Exception('The website is already initialized');
            }
        }
        if (!@mkdir($this->getWebsitePath() . '/' . self::PHPOOLE_DIRNAME)) {
            if ($force !== true) {
                throw new Exception('Cannot create root PHPoole directory');
            }
        }
        $this->addMessage(self::PHPOOLE_DIRNAME . ' directory');
        $this->addMessage(Skeleton::createConfigFile($this));
        $this->addMessage(Skeleton::createLayoutsDir($this));
        $this->addMessage(Skeleton::createLayoutDefaultFile($this)); // optional
        $this->addMessage(Skeleton::createAssetsDir($this));
        $this->addMessage(Skeleton::createContentDir($this));
        $this->addMessage(Skeleton::createContentDefaultFile($this)); // optional
        $this->addMessage(Skeleton::createRouterFile($this));

        $this->triggerEvent(__FUNCTION__, func_get_args(), 'post');

        return $this->getMessages();
    }

    /**
     * Get config from config.ini file
     *
     * @param string $key
     * @return array|null
     * @throws Exception
     */
    public function getConfig($key='')
    {
        if ($this->_config == null) {
            $configFilePath = $this->getWebsitePath() . '/' . self::PHPOOLE_DIRNAME . '/' . self::CONFIG_FILENAME;
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
            $this->_config;
        }
        return $this->_config;
    }

    /**
     * Load pages files from content/pages
     *
     * @return $this
     * @throws Exception
     */
    public function loadPages()
    {
        $pageInfo  = array();
        $pageIndex = array();
        $pageData  = array();
        $pagesPath = $this->getWebsitePath() . '/' . self::PHPOOLE_DIRNAME . '/' . self::CONTENT_DIRNAME . '/' . self::CONTENT_PAGES_DIRNAME;
        // Iterate pages files, filtered by markdown "md" extension
        $pagesIterator = new Spl\FileIterator($pagesPath, 'md');
        foreach ($pagesIterator as $filePage) {
            $pageInfo = $filePage->parse()->getData('info');
            $pageIndex = ($pagesIterator->getSubPath() ? $pagesIterator->getSubPath() : 'home');
            $pageData = $pageInfo;
            //
            $pageData['title'] = (
                isset($pageInfo['title']) && !empty($pageInfo['title'])
                ? $pageInfo['title']
                : ucfirst($filePage->getBasename('.md'))
            );
            $pageData['path'] = str_replace(DS, '/', $pagesIterator->getSubPath());
            $pageData['basename'] = $filePage->getBasename('.md') . '.html';
            $pageData['layout'] = (
                isset($pageInfo['layout'])
                    && is_file($this->getWebsitePath() . '/' . self::PHPOOLE_DIRNAME . '/' . self::LAYOUTS_DIRNAME . '/' . (isset($this->getConfig()['site']['layouts']) ? $this->getConfig()['site']['layouts'] . '/' : '') . $pageInfo['layout'] . '.html')
                ? $pageInfo['layout'] . '.html'
                : 'default.html'
            );
            // in case of external content
            if (isset($pageInfo['content']) /* && is valid URL to md file */) {
                if (false === ($pageContent = @file_get_contents($pageInfo['content'], false))) {
                    throw new Exception(sprintf("Cannot get contents from %s\n", $filePage->getFilename()));
                }
            }
            else {
                $pageContent = $filePage->getData('content_raw');
            }
            // content processing
            $pageData['content'] = $this->process($pageContent);

            // event postloop
            $results = $this->triggerEvent(__FUNCTION__, array(
                'pageInfo'  => $pageInfo,
                'pageIndex' => $pageIndex,
                'pageData'  => $pageData
            ), 'postloop');
            if ($results) {
                extract($results);
            }

            // add page details
            $this->addPage($pageIndex, $pageData);
            // menu
            if (isset($pageInfo['menu'])) { // "nav" for example
                $menuEntry = (
                    !empty($pageInfo['menu'])
                    ? array(
                        'title' => $pageInfo['title'],
                        'path'  => str_replace(DS, '/', $pagesIterator->getSubPath())
                    )
                    : ''
                );
                $this->addMenuEntry($pageInfo['menu'], $menuEntry);
            }
            unset($pageInfo);
            unset($pageIndex);
            unset($pageData);
        }
        return $this;
    }

    /**
     * Process Markdown content
     *
     * @param $rawContent
     * @return string
     * @throws Exception
     */
    public function process($rawContent)
    {
        $this->_processor = new MarkdownExtra;
        $this->_processor->code_attr_on_pre = true;
        $this->_processor->predef_urls = array('base_url' => $this->getConfig()['site']['base_url']);
        return $this->_processor->transform($rawContent);
    }

    /**
     * Temporary method to prepare (sort) "nav" menu
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
     * Generate static files
     *
     * @return array
     * @throws Exception
     */
    public function generate()
    {
        $pages = $this->getPages();
        $menuNav = $this->prepareMenuNav();            
        if (isset($this->getConfig()['site']['layouts'])) {
            $tplEngine = $this->tplEngine($this->getWebsitePath() . '/' . self::PHPOOLE_DIRNAME . '/' . self::LAYOUTS_DIRNAME . '/' . $this->getConfig()['site']['layouts']);
        }
        else {
            $tplEngine = $this->tplEngine($this->getWebsitePath() . '/' . self::PHPOOLE_DIRNAME . '/' . self::LAYOUTS_DIRNAME);
        }
        $pagesIterator = (new \ArrayObject($pages))->getIterator();
        $pagesIterator->ksort();
        $this->clearMessages();
        while ($pagesIterator->valid()) {
            $page = $pagesIterator->current();
            // template variables
            $pageExtra = array(
                'nav' => (isset($menuNav) ? $menuNav : ''),
            );
            $tplVariables = array(
                'phpoole' => array(
                    'version' => PHPoole::VERSION,
                    'url'     => PHPoole::URL
                ),
                'site' => new Proxy($this),
                'page' => array_merge($page, $pageExtra),
            );
            // rendering
            $rendered = $tplEngine->render($page['layout'], $tplVariables);
            // dir/file writing
            if (!is_dir($this->getWebsitePath() . '/' . $page['path'])) {
                if (!@mkdir($this->getWebsitePath() . '/' . $page['path'], 0777, true)) {
                    throw new Exception(sprintf('Cannot create %s', $this->getWebsitePath() . '/' . $page['path']));
                }
            }
            if (is_file($this->getWebsitePath() . '/' . ($page['path'] != '' ? $page['path'] . '/' : '') . $page['basename'])) {
                if (!@unlink($this->getWebsitePath() . '/' . ($page['path'] != '' ? $page['path'] . '/' : '') . $page['basename'])) {
                    throw new Exception(sprintf('Cannot delete %s%s', ($page['path'] != '' ? $page['path'] . '/' : ''), $page['basename']));
                }
                $this->addMessage('Delete ' . ($page['path'] != '' ? $page['path'] . '/' : '') . $page['basename'], true);
            }
            if (!@file_put_contents(sprintf('%s%s', $this->getWebsitePath() . '/' . ($page['path'] != '' ? $page['path'] . '/' : ''), $page['basename']), $rendered)) {
                throw new Exception(sprintf('Cannot write %s%s', ($page['path'] != '' ? $page['path'] . '/' : ''), $page['basename']));
            }
            $this->addMessage(sprintf("Write %s%s", ($page['path'] != '' ? $page['path'] . '/' : ''), $page['basename']), true);

            // event postloop
            $this->triggerEvent(__FUNCTION__, array(
                'page' => $page
            ), 'postloop');
            $pagesIterator->next();
        }
        $this->addMessage('Write pages');
        // copy assets
        if (is_dir($this->getWebsitePath() . '/' . self::ASSETS_DIRNAME)) {
            Util::rmDir($this->getWebsitePath() . '/' . self::ASSETS_DIRNAME);
        }
        Util::copy($this->getWebsitePath() . '/' . self::PHPOOLE_DIRNAME . '/' . self::ASSETS_DIRNAME, $this->getWebsitePath() . '/' . self::ASSETS_DIRNAME);
        // Done!
        $this->addMessage('copy assets directory (and sub)');
        $this->addMessage(Skeleton::createReadmeFile($this));
        return $this->getMessages();
    }

    /**
     * Temporary method to wrap Twig (and more?) engine
     *
     * @param $templatesPath
     * @return \Twig_Environment
     */
    public function tplEngine($templatesPath)
    {
        $twigLoader = new \Twig_Loader_Filesystem($templatesPath);
        $twig = new \Twig_Environment($twigLoader, array(
            'autoescape' => false,
            'debug'      => true
        ));
        $twig->addExtension(new \Twig_Extension_Debug());
        return $twig;
    }

    /**
     * Return a console displayable tree of pages
     *
     * @return Spl\FilenameRecursiveTreeIterator
     * @throws Exception
     */
    public function getPagesTree()
    {
        $pagesPath = $this->getWebsitePath() . '/' . self::PHPOOLE_DIRNAME . '/' . self::CONTENT_DIRNAME . '/' . self::CONTENT_PAGES_DIRNAME;
        if (!is_dir($pagesPath)) {
            throw new Exception(sprintf("Invalid %s/%s directory", self::CONTENT_DIRNAME, self::CONTENT_PAGES_DIRNAME));
        }
        $dirIterator = new \RecursiveDirectoryIterator($pagesPath, \RecursiveDirectoryIterator::SKIP_DOTS);
        $pages = new Spl\FilenameRecursiveTreeIterator(
            $dirIterator,
            Spl\FilenameRecursiveTreeIterator::SELF_FIRST
        );
        return $pages;
    }

    /**
     * Loads plugins in the plugins/ directory if exist
     */
    private function loadPlugins()
    {
        try {
            $configPlugins = $this->getConfig('plugins');
        } catch (Exception $e) {
            $configPlugins = array();
        }
        $pluginsDirCore = __DIR__ . '/' . self::PLUGINS_DIRNAME;
        $pluginsDir     = $this->getWebsitePath() . '/' . self::PHPOOLE_DIRNAME . '/' . self::PLUGINS_DIRNAME;
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
                    include_once($plugin->getPathname() . "/Plugin.php");
                    if (class_exists($pluginClass)) {
                        $pluginObject = new $pluginClass($this->getEvents());
                        // init
                        if (method_exists($pluginObject, 'preInit')) {
                            $this->getEvents()->attach('init.pre', array($pluginObject, 'preInit'));
                        }
                        if (method_exists($pluginObject, 'postInit')) {
                            $this->getEvents()->attach('init.post', array($pluginObject, 'postInit'));
                        }
                        // load pages
                        if (method_exists($pluginObject, 'postloopLoadPages')) {
                            $this->getEvents()->attach('loadPages.postloop', array($pluginObject, 'postloopLoadPages'));
                        }
                        // generate
                        if (method_exists($pluginObject, 'postloopGenerate')) {
                            $this->getEvents()->attach('generate.postloop', array($pluginObject, 'postloopGenerate'));
                        }
                    }
                }
            }
        }
    }
}
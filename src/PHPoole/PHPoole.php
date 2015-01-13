<?php
/**
 * PHPoole is a light and easy static website generator written in PHP.
 * @see http://phpoole.narno.org
 *
 * @author Arnaud Ligny <arnaud@ligny.org>
 * @license The MIT License (MIT)
 *
 * Copyright (c) 2013-2014 Arnaud Ligny
 */

namespace PHPoole;

use Zend\Console\ColorInterface as Color;
use Zend\EventManager\EventManager;
use Zend\Loader\PluginClassLoader;
use Michelf\MarkdownExtra;
use PHPoole\Util;
use PHPoole\Spl;

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

    public function __construct($websitePath)
    {
        $splFileInfo = new \SplFileInfo($websitePath);
        if (!$splFileInfo->isDir()) {
            throw new \Exception('Invalid directory provided');
        }
        else {
            $this->_websiteFileInfo = $splFileInfo;
            $this->_websitePath = $splFileInfo->getRealPath();
        }
        // Load plugins
        $this->_events = new EventManager();
        $this->loadPlugins();
    }

    public function getWebsiteFileInfo()
    {
        return $this->_websiteFileInfo;
    }

    public function setWebsitePath($path)
    {
        $this->_websitePath = $path;
        return $this->getWebsitePath();
    }

    public function getWebsitePath()
    {
        return $this->_websitePath;
    }

    public function getEvents()
    {
        return $this->_events;
    }

    public function getMessages()
    {
        return $this->_messages;
    }

    public function addMessage($message, $verbose=false)
    {
        if (!$verbose) {
            $this->_messages[] = $message;
        }
        return $this->getMessages();
    }

    public function clearMessages()
    {
        $this->_messages = array();
    }

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

    public function addPage($index, $data)
    {
        $this->_pages[$index] = $data;
        return $this->getPages();
    }

    public function getMenu($menu='')
    {
        if (!empty($menu) && array_key_exists($menu, $this->_menu)) {
            return $this->_menu[$menu];
        }
        return $this->_menu;
    }

    public function addMenuEntry($menu, $entry)
    {
        $this->_menu[$menu][] = $entry;
        return $this->getMenu($menu);
    }

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

    // temporay method
    public function setLocalServe($status)
    {
        return $this->localServe = $status;
    }

    /**
     * Initialization of a new PHPoole website
     * @param  boolean $force Remove if already initialized
     * @return array Messages
     */
    public function init($force=false)
    {
        $this->triggerEvent(__FUNCTION__, func_get_args(), 'pre');

        if (file_exists($this->getWebsitePath() . '/' . self::PHPOOLE_DIRNAME . '/' . self::CONFIG_FILENAME)) {
            if ($force === true) {
                Util::RecursiveRmdir($this->getWebsitePath() . '/' . self::PHPOOLE_DIRNAME);
            }
            else {
                throw new \Exception('The website is already initialized');
            }
        }
        if (!@mkdir($this->getWebsitePath() . '/' . self::PHPOOLE_DIRNAME)) {
            throw new \Exception('Cannot create root PHPoole directory');
        }
        $this->addMessage(self::PHPOOLE_DIRNAME . ' directory');
        $this->addMessage($this->createConfigFile());
        $this->addMessage($this->createLayoutsDir());
        $this->addMessage($this->createLayoutDefaultFile()); // optional
        $this->addMessage($this->createAssetsDir());
        //$this->addMessage($this->createAssetDefaultFiles());
        $this->addMessage($this->createContentDir());
        $this->addMessage($this->createContentDefaultFile()); // optional
        $this->addMessage($this->createRouterFile());

        $this->triggerEvent(__FUNCTION__, func_get_args(), 'post');

        return $this->getMessages();
    }

    private function createConfigFile()
    {
        $content = <<<'EOT'
[site]
name        = "PHPoole"
baseline    = "Light and easy static website generator!"
description = "PHPoole is a light and easy static website / blog generator written in PHP. It parses your content written with Markdown, merge it with layouts and generates static HTML files."
base_url    = "http://localhost:8000"
language    = "en"
[author]
name  = "Arnaud Ligny"
email = "arnaud+phpoole@ligny.org"
home  = "http://narno.org"
[deploy]
repository = "https://github.com/Narno/PHPoole.git"
branch     = "gh-pages"
EOT;
        if (!@file_put_contents($this->getWebsitePath() . '/' . self::PHPOOLE_DIRNAME . '/' . self::CONFIG_FILENAME, $content)) {
            throw new \Exception('Cannot create the config file');
        }
        return 'Config file';
    }

    private function createLayoutsDir()
    {
        if (!@mkdir($this->getWebsitePath() . '/' . self::PHPOOLE_DIRNAME . '/' . self::LAYOUTS_DIRNAME)) {
            throw new \Exception('Cannot create the layouts directory');
        }
        return 'Layouts directory';
    }

    private function createLayoutDefaultFile()
    {
        $content = <<<'EOT'
<!DOCTYPE html>
<!--[if IE 8]><html class="no-js lt-ie9" lang="{{ site.language }}"><![endif]-->
<!--[if gt IE 8]><!--><html class="no-js" lang="{{ site.language }}"><!--<![endif]-->
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width">
  <meta name="description" content="{{ site.description }}">
  <title>{{ site.name}} - {{ page.title }}</title>
  <style type="text/css">
    body { font: bold 24px Helvetica, Arial; padding: 15px 20px; color: #ddd; background: #333;}
    a:link {text-decoration: none; color: #fff;}
    a:visited {text-decoration: none; color: #fff;}
    a:active {text-decoration: none; color: #fff;}
    a:hover {text-decoration: underline; color: #fff;}
  </style>
</head>
<body>
  <a href="{{ site.base_url}}"><strong>{{ site.name }}</strong></a><br />
  <em>{{ site.baseline }}</em>
  <hr />
  <p>{{ page.content }}</p>
  <hr />
  <p>Powered by <a href="http://phpoole.narno.org">PHPoole</a>, coded by <a href="{{ site.author.home }}">{{ site.author.name }}</a></p>
</body>
</html>
EOT;
        if (!@file_put_contents($this->getWebsitePath() . '/' . self::PHPOOLE_DIRNAME . '/' . self::LAYOUTS_DIRNAME . '/default.html', $content)) {
            throw new \Exception('Cannot create the default layout file');
        }
        return 'Default layout file';
    }

    private function createAssetsDir()
    {
        $subDirList = array(
            self::ASSETS_DIRNAME,
            self::ASSETS_DIRNAME . '/css',
            self::ASSETS_DIRNAME . '/img',
            self::ASSETS_DIRNAME . '/js',
        );
        foreach ($subDirList as $subDir) {
            if (!@mkdir($this->getWebsitePath() . '/' . self::PHPOOLE_DIRNAME . '/' . $subDir)) {
                throw new \Exception('Cannot create the assets directory');
            }
        }
        return 'Assets directory';
    }

    private function createAssetDefaultFiles()
    {
        return 'Default assets files not needed';
    }

    private function createContentDir()
    {
        $subDirList = array(
            self::CONTENT_DIRNAME,
            self::CONTENT_DIRNAME . '/' . self::CONTENT_PAGES_DIRNAME,
        );
        foreach ($subDirList as $subDir) {
            if (!@mkdir($this->getWebsitePath() . '/' . self::PHPOOLE_DIRNAME . '/' . $subDir)) {
                throw new \Exception('Cannot create the content directory');
            }
        }
        return 'Content directory';
    }

    private function createContentDefaultFile()
    {
        $content = <<<'EOT'
<!--
title = Home
layout = default
menu = nav
-->
PHPoole is a light and easy static website / blog generator written in PHP.
It parses your content written with Markdown, merge it with layouts and generates static HTML files.

PHPoole = [PHP](http://www.php.net) + [Poole](http://en.wikipedia.org/wiki/Strange_Case_of_Dr_Jekyll_and_Mr_Hyde#Mr._Poole)

Go to the [dedicated website](http://phpoole.narno.org) for more details.
EOT;
        if (!@file_put_contents($this->getWebsitePath() . '/' . self::PHPOOLE_DIRNAME . '/' . self::CONTENT_DIRNAME . '/' . self::CONTENT_PAGES_DIRNAME . '/index.md', $content)) {
            throw new \Exception('Cannot create the default content file');
        }
        return 'Default content file';
    }

    private function createRouterFile()
    {
        $content = <<<'EOT'
<?php
date_default_timezone_set("UTC");
define("DIRECTORY_INDEX", "index.html");
$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$ext = pathinfo($path, PATHINFO_EXTENSION);
if (empty($ext)) {
    $path = rtrim($path, "/") . "/" . DIRECTORY_INDEX;
}
if (file_exists($_SERVER["DOCUMENT_ROOT"] . $path)) {
    return false;
}
http_response_code(404);
echo "404, page not found";
EOT;
        if (!@file_put_contents($this->getWebsitePath() . '/' . self::PHPOOLE_DIRNAME . '/router.php', $content)) {
            throw new \Exception('Cannot create the router file');
        }
        return 'Router file';
    }

    private function createReadmeFile()
    {
        $content = <<<'EOT'
Powered by [PHPoole](http://phpoole.narno.org).
EOT;
            
        if (is_file($this->getWebsitePath() . '/README.md')) {
            if (!@unlink($this->getWebsitePath() . '/README.md')) {
                throw new \Exception('Cannot create the README file');
            }
        }
        if (!@file_put_contents($this->getWebsitePath() . '/README.md', $content)) {
            throw new \Exception('Cannot create the README file');
        }
        return 'Create README file';
    }

    /**
     * Get config from config.ini file
     * @param  string $key
     * @return array
     */
    public function getConfig($key='')
    {
        if ($this->_config == null) {
            $configFilePath = $this->getWebsitePath() . '/' . self::PHPOOLE_DIRNAME . '/' . self::CONFIG_FILENAME;
            if (!file_exists($configFilePath)) {
                throw new \Exception('Cannot get config file');
            }
            if (!($this->_config = parse_ini_file($configFilePath, true))) {
                throw new \Exception('Cannot parse config file');
            }
            if (!empty($key)) {
                if (!array_key_exists($key, $this->_config)) {
                    throw new \Exception(sprintf('Cannot find %s key in config file', $key));
                }
                return $this->_config[$key];
            }
            $this->_config;
        }
        return $this->_config;
    }

    /**
     * Load pages files from content/pages
     * @return object PHPoole\PHPoole
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
            if (isset($pageInfo['pagination'])) {
                $pageData['pagination'] = $pageInfo['pagination'];
            }
            // in case of external content
            if (isset($pageInfo['content']) /* && is valid URL to md file */) {
                if (false === ($pageContent = @file_get_contents($pageInfo['content'], false))) {
                    throw new \Exception(sprintf("Cannot get contents from %s\n", $filePage->getFilename()));
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

    public function process($rawContent)
    {
        // Markdown only
        $this->_processor = new MarkdownExtra;
        $this->_processor->code_attr_on_pre = true;
        $this->_processor->predef_urls = array('base_url' => $this->getConfig()['site']['base_url']);
        // [my base url][base_url]
        return $this->_processor->transform($rawContent);
    }

    /**
     * Temporary method to prepare (sort) "nav" menu
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
     * @return array Messages
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
        $currentPos = 0;
        $prevPos = '';
        $this->clearMessages();
        while ($pagesIterator->valid()) {
            // pagination
            $previous = $next = '';
            $prevTitle = $nextTitle = '';
            $page = $pagesIterator->current();
            if (isset($page['pagination']) && $page['pagination'] == 'enabled') {
                if ($pagesIterator->offsetExists($prevPos)) {
                    $previous = $pagesIterator->offsetGet($prevPos)['path'];
                    $prevTitle = $pagesIterator->offsetGet($prevPos)['title'];
                }
                $pagesIterator->next();
                if ($pagesIterator->valid()) {
                    if (isset($pagesIterator->current()['pagination']) && $pagesIterator->current()['pagination'] == 'enabled') {
                        $next = $pagesIterator->current()['path'];
                        $nextTitle = $pagesIterator->current()['title'];
                    }
                }
                $pagesIterator->seek($currentPos);
            }
            // template variables
            $pageExtra = array(
                'nav'      => (isset($menuNav) ? $menuNav : ''),
                'previous' => (isset($previous) ? array('path' => $previous, 'title' => $prevTitle) : ''),
                'next'     => (isset($next) ? array('path' => $next, 'title' => $nextTitle) : ''),
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
                    throw new \Exception(sprintf('Cannot create %s', $this->getWebsitePath() . '/' . $page['path']));
                }
            }
            if (is_file($this->getWebsitePath() . '/' . ($page['path'] != '' ? $page['path'] . '/' : '') . $page['basename'])) {
                if (!@unlink($this->getWebsitePath() . '/' . ($page['path'] != '' ? $page['path'] . '/' : '') . $page['basename'])) {
                    throw new \Exception(sprintf('Cannot delete %s%s', ($page['path'] != '' ? $page['path'] . '/' : ''), $page['basename']));
                }
                $this->addMessage('Delete ' . ($page['path'] != '' ? $page['path'] . '/' : '') . $page['basename'], true);
            }
            if (!@file_put_contents(sprintf('%s%s', $this->getWebsitePath() . '/' . ($page['path'] != '' ? $page['path'] . '/' : ''), $page['basename']), $rendered)) {
                throw new \Exception(sprintf('Cannot write %s%s', ($page['path'] != '' ? $page['path'] . '/' : ''), $page['basename']));
            }
            $this->addMessage(sprintf("Write %s%s", ($page['path'] != '' ? $page['path'] . '/' : ''), $page['basename']), true);

            // event postloop
            $this->triggerEvent(__FUNCTION__, array(
                'page' => $page
            ), 'postloop');

            // use by the next iteration
            $prevPos = $pagesIterator->key();
            $currentPos++;
            $pagesIterator->next();
        }
        $this->addMessage('Write pages');
        // Copy assets
        if (is_dir($this->getWebsitePath() . '/' . self::ASSETS_DIRNAME)) {
            Util::RecursiveRmdir($this->getWebsitePath() . '/' . self::ASSETS_DIRNAME);
        }
        Util::RecursiveCopy($this->getWebsitePath() . '/' . self::PHPOOLE_DIRNAME . '/' . self::ASSETS_DIRNAME, $this->getWebsitePath() . '/' . self::ASSETS_DIRNAME);
        // Done!
        $this->addMessage('Copy assets directory (and sub)');
        $this->addMessage($this->createReadmeFile());
        return $this->getMessages();
    }

    /**
     * Temporary method to wrap Twig (and more?) engine
     * @param  string $templatesPath Absolute path to templates files
     * @return object Twig
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
     * Return pages list
     * @param  string $subDir
     * @return array (path, url and title)
     */
    /*public function getPagesPath($subDir='')
    {
        $pagesPath = $this->getWebsitePath() . '/' . self::PHPOOLE_DIRNAME . '/' . self::CONTENT_DIRNAME . '/' . self::CONTENT_PAGES_DIRNAME;
        $pagesPath = (
            !empty($subDir)
            ? $pagesPath . '/' . $subDir
            : $pagesPath
        );
        if (!is_dir($pagesPath)) {
            throw new \Exception(sprintf("Invalid %s/%s%s directory", self::CONTENT_DIRNAME, self::CONTENT_PAGES_DIRNAME, (!empty($subDir) ? '/' . $subDir : '')));
        }
        $pagesIterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $pagesPath,
                \RecursiveDirectoryIterator::SKIP_DOTS
            ),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($pagesIterator as $page) {
            if ($page->isDir()) {
                if (file_exists($page->getPathname() . '/index.md')) {
                    $pages[] = array(
                        'path'  => (!empty($subDir) ? $subDir . '/' : '')  . $pagesIterator->getSubPathName(),
                        'title' => (new FileInfo($page->getPathname() . '/index.md'))
                            ->parse($this->getConfig())->getData('info')['title'],
                    );
                }
            }
        }
        return $pages;
    }*/

    /**
     * Return a console displayable tree of pages
     * @return iterator
     */
    public function getPagesTree()
    {
        $pagesPath = $this->getWebsitePath() . '/' . self::PHPOOLE_DIRNAME . '/' . self::CONTENT_DIRNAME . '/' . self::CONTENT_PAGES_DIRNAME;
        if (!is_dir($pagesPath)) {
            throw new \Exception(sprintf("Invalid %s/%s directory", self::CONTENT_DIRNAME, self::CONTENT_PAGES_DIRNAME));
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
     * @return void
     */
    private function loadPlugins()
    {
        try {
            $configPlugins = $this->getConfig('plugins');
        } catch (\Exception $e) {
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
                        // loadpages
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
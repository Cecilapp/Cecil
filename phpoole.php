#!/usr/bin/env php
<?php
/**
 * PHPoole is a light and easy static website generator written in PHP.
 * @see http://narno.org/PHPoole/
 *
 * @author Arnaud Ligny <arnaud@ligny.org>
 * @license The MIT License (MIT)
 *
 * Copyright (c) 2013 Arnaud Ligny
 */

//error_reporting(0);

use Zend\Console\Console;
use Zend\Console\ColorInterface as Color;
use Zend\Console\Getopt;
use Zend\Console\Exception\RuntimeException as ConsoleException;
use Zend\EventManager\EventManager;
use Michelf\MarkdownExtra;

// Composer autoloading
if (file_exists('vendor/autoload.php')) {
    $loader = include 'vendor/autoload.php';
}
else {
    echo 'Run the following commands:' . PHP_EOL;
    if (!file_exists('composer.json')) {
        echo 'curl https://raw.github.com/Narno/PHPoole/master/composer.json > composer.json' . PHP_EOL;
    }
    if (!file_exists('composer.phar')) {
        echo 'curl -s http://getcomposer.org/installer | php' . PHP_EOL;
    }  
    echo 'php composer.phar install' . PHP_EOL;
    exit(2);
}

try {
    $console = Console::getInstance();
    $phpooleConsole = new PHPooleConsole($console);
} catch (ConsoleException $e) {
    // Could not get console adapter - most likely we are not running inside a console window.
}

define('DS', DIRECTORY_SEPARATOR);
define('PHPOOLE_DIRNAME', '_phpoole');
$websitePath = '';//getcwd();

// Defines rules
$rules = array(
    'help|h'     => 'Get PHPoole usage message',
    'init|i-s'   => 'Build a new PHPoole website (<force>)',
    'generate|g' => 'Generate static files',
    'serve|s'    => 'Start built-in web server',
    'deploy|d'   => 'Deploy static files',
    'list|l=s'   => 'Lists <pages> or <posts>',
);

// Get and parse console options
try {
    $opts = new Getopt($rules);
    $opts->parse();
} catch (ConsoleException $e) {
    echo $e->getUsageMessage();
    exit(2);
}

// help option
if ($opts->getOption('help') || count($opts->getOptions()) == 0) {
    echo $opts->getUsageMessage();
    exit(0);
}

// Get provided directory if exist
$remainingArgs = $opts->getRemainingArgs();
if (isset($remainingArgs[0])) {
    if (!is_dir($remainingArgs[0])) {
        $phpooleConsole->wlError('Invalid directory provided');
        exit(2);
    }
    $websitePath = str_replace(DS, '/', realpath($remainingArgs[0]));
}

// Instanciate PHPoole API
try {
    $phpoole = new PHPoole($websitePath);
}
catch (Exception $e) {
    $phpooleConsole->wlError($e->getMessage());
    exit(2);
}

// init option
if ($opts->getOption('init')) {
    $force = false;
    $phpooleConsole->wlInfo('Initializing new website');
    if ((string)$opts->init == 'force') {
        $force = true;
    }
    try {
        $messages = $phpoole->init($force);
        foreach ($messages as $message) {
            $phpooleConsole->wlDone($message);
        }
    }  
    catch (Exception $e) {
        $phpooleConsole->wlError($e->getMessage());
    }
}

// generate option
if ($opts->getOption('generate')) {
    $generateConfig = array();
    $phpooleConsole->wlInfo('Generate website');
    if (isset($opts->serve)) {
        $generateConfig['site']['base_url'] = 'http://localhost:8000';
        $phpooleConsole->wlInfo('Youd should re-generate before deploy');
    }
    try {
        $messages = $phpoole->generate($generateConfig);
        foreach ($messages as $message) {
            $phpooleConsole->wlDone($message);
        }
    }  
    catch (Exception $e) {
        $phpooleConsole->wlError($e->getMessage());
    }
}

// serve option
if ($opts->getOption('serve')) {
    if (version_compare(PHP_VERSION, '5.4.0', '<')) {
        $phpooleConsole->wlError('PHP 5.4+ required to run built-in server (your version: ' . PHP_VERSION . ')');
        exit(2);
    }
    if (!is_file(sprintf('%s/%s/router.php', $websitePath, PHPOOLE_DIRNAME))) {
        $phpooleConsole->wlError('Router not found');
        exit(2);
    }
    $phpooleConsole->wlInfo(sprintf("Start server http://%s:%d", 'localhost', '8000'));
    if (isWindows()) {
        $command = sprintf(
            //'START /B php -S %s:%d -t %s %s > nul',
            'START php -S %s:%d -t %s %s > nul',
            'localhost',
            '8000',
            $websitePath,
            sprintf('%s/%s/router.php', $websitePath, PHPOOLE_DIRNAME)
        );
    }
    else {
        echo 'Ctrl-C to stop it.' . PHP_EOL;
        $command = sprintf(
            //'php -S %s:%d -t %s %s >/dev/null 2>&1 & echo $!',
            'php -S %s:%d -t %s %s >/dev/null',
            'localhost',
            '8000',
            $websitePath,
            sprintf('%s/%s/router.php', $websitePath, PHPOOLE_DIRNAME)
        );
    }
    exec($command);
}

// deploy option
if ($opts->getOption('deploy')) {
    $phpooleConsole->wlInfo('Deploy website on GitHub');
    try {
        $config = $phpoole->getConfig();
        if (!isset($config['deploy']['repository']) && !isset($config['deploy']['branch'])) {
            throw new Exception('Cannot found the repository name in the config file');
        }
        else {
            $repoUrl = $config['deploy']['repository'];
            $repoBranch = $config['deploy']['branch'];
        }
        $deployDir = $phpoole->getWebsitePath() . '/../.' . basename($phpoole->getWebsitePath());
        if (is_dir($deployDir)) {
            //echo 'Deploying files to GitHub...' . PHP_EOL;
            $deployIterator = new FilesystemIterator($deployDir);
            foreach ($deployIterator as $deployFile) {
                if ($deployFile->isFile()) {
                    @unlink($deployFile->getPathname());
                }
                if ($deployFile->isDir() && $deployFile->getFilename() != '.git') {
                    RecursiveRmDir($deployFile->getPathname());
                }
            }
            RecursiveCopy($phpoole->getWebsitePath(), $deployDir);
            $updateRepoCmd = array(
                'add -A',
                'commit -m "Update ' . $repoBranch . ' via PHPoole"',
                'push github ' . $repoBranch . ' --force'
            );
            runGitCmd($deployDir, $updateRepoCmd);
        }
        else {
            //echo 'Setting up GitHub deployment...' . PHP_EOL;
            @mkdir($deployDir);
            RecursiveCopy($phpoole->getWebsitePath(), $deployDir);
            $initRepoCmd = array(
                'init',
                'add -A',
                'commit -m "Create ' . $repoBranch . ' via PHPoole"',
                'branch -M ' . $repoBranch . '',
                'remote add github ' . $repoUrl,
                'push github ' . $repoBranch . ' --force'
            );
            runGitCmd($deployDir, $initRepoCmd);
        }
    }  
    catch (Exception $e) {
        $phpooleConsole->wlError($e->getMessage());
    }
}

// list option
if ($opts->getOption('list')) {
    if (isset($opts->list) && $opts->list == 'pages') {
        try {
            $phpooleConsole->wlInfo('List pages');
            $pages = $phpoole->getPagesTree();
            if ($console->isUtf8()) {
                $unicodeTreePrefix = function(RecursiveTreeIterator $tree) {
                    $prefixParts = [
                        RecursiveTreeIterator::PREFIX_LEFT         => ' ',
                        RecursiveTreeIterator::PREFIX_MID_HAS_NEXT => '│ ',
                        RecursiveTreeIterator::PREFIX_END_HAS_NEXT => '├ ',
                        RecursiveTreeIterator::PREFIX_END_LAST     => '└ '
                    ];
                    foreach ($prefixParts as $part => $string) {
                        $tree->setPrefixPart($part, $string);
                    }
                };
                $unicodeTreePrefix($pages);
            }
            $console->writeLine(PHPoole::PHPOOLE_DIRNAME);
            foreach($pages as $page) {
                $console->writeLine($page);
            }
        }
        catch (Exception $e) {
            $phpooleConsole->wlError($e->getMessage());
        }
    }
    else if (isset($opts->list) && $opts->list == 'posts') {
        $phpooleConsole->wlInfo('List posts');
        // @todo todo! :-)
    }
    else {
        echo $opts->getUsageMessage();
        exit(2);
    }
}

/**
 * PHPoole API
 */
class PHPoole
{
    const PHPOOLE_DIRNAME = '_phpoole';
    const CONFIG_FILENAME = 'config.ini';
    const LAYOUTS_DIRNAME = 'layouts';
    const ASSETS_DIRNAME  = 'assets';
    const CONTENT_DIRNAME = 'content';
    const CONTENT_PAGES_DIRNAME = 'pages';
    const CONTENT_POSTS_DIRNAME = 'posts';
    const PLUGINS_DIRNAME  = 'plugins';

    protected $_websitePath;
    protected $_websiteFileInfo;
    protected $_events;
    protected $_messages;

    public function __construct($websitePath)
    {
        $splFileInfo = new SplFileInfo($websitePath);
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

    public function setMessage($message)
    {
        $this->_messages[] = $message;
        return $this->getMessages();
    }

    public function init($force=false)
    {
        $this->getEvents()->trigger(__FUNCTION__ . '.pre', $this, compact('force'));

        if (file_exists($this->getWebsitePath() . '/' . self::PHPOOLE_DIRNAME . '/' . self::CONFIG_FILENAME)) {
            if ($force === true) {
                RecursiveRmdir($this->getWebsitePath() . '/' . self::PHPOOLE_DIRNAME);
            }
            else {
                throw new Exception('The website is already initialized');
            }
        }
        if (!@mkdir($this->getWebsitePath() . '/' . self::PHPOOLE_DIRNAME)) {
            throw new Exception('Cannot create root PHPoole directory');
        }
        $this->setMessage(self::PHPOOLE_DIRNAME . ' directory created');
        $this->setMessage($this->createConfigFile());
        $this->setMessage($this->createLayoutsDir());
        $this->setMessage($this->createLayoutDefaultFile());
        $this->setMessage($this->createAssetsDir());
        $this->setMessage($this->createAssetDefaultFiles());
        $this->setMessage($this->createContentDir());
        $this->setMessage($this->createContentDefaultFile());
        $this->setMessage($this->createRouterFile());

        $this->getEvents()->trigger(__FUNCTION__ . '.post', $this, compact('force'));

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
            throw new Exception('Cannot create the config file');
        }
        return 'Config file created';
    }

    private function createLayoutsDir()
    {
        if (!@mkdir($this->getWebsitePath() . '/' . self::PHPOOLE_DIRNAME . '/' . self::LAYOUTS_DIRNAME)) {
            throw new Exception('Cannot create the layouts directory');
        }
        return 'Layouts directory created';
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
  <title>{{ site.name}} - {{ title }}</title>
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
  <p>{{ content }}</p>
  <hr />
  <p>Powered by <a href="http://narno.org/PHPoole">PHPoole</a>, coded by <a href="{{ author.home }}">{{ author.name }}</a></p>
</body>
</html>
EOT;
        if (!@file_put_contents($this->getWebsitePath() . '/' . self::PHPOOLE_DIRNAME . '/' . self::LAYOUTS_DIRNAME . '/default.html', $content)) {
            throw new Exception('Cannot create the default layout file');
        }
        return 'Default layout file created';
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
                throw new Exception('Cannot create the assets directory');
            }
        }
        return 'Assets directory created';
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
            self::CONTENT_DIRNAME . '/' . self::CONTENT_POSTS_DIRNAME,
        );
        foreach ($subDirList as $subDir) {
            if (!@mkdir($this->getWebsitePath() . '/' . self::PHPOOLE_DIRNAME . '/' . $subDir)) {
                throw new Exception('Cannot create the content directory');
            }
        }
        return 'Content directory created';
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

Go to the [dedicated website](http://narno.org/PHPoole) for more details.
EOT;
        if (!@file_put_contents($this->getWebsitePath() . '/' . self::PHPOOLE_DIRNAME . '/' . self::CONTENT_DIRNAME . '/' . self::CONTENT_PAGES_DIRNAME . '/index.md', $content)) {
            throw new Exception('Cannot create the default content file');
        }
        return 'Default content file created';
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
            throw new Exception('Cannot create the router file');
        }
        return 'Router file created';
    }

    private function createReadmeFile()
    {
        $content = <<<'EOT'
Powered by [PHPoole](http://narno.org/PHPoole/).
EOT;
        
        if (is_file($this->getWebsitePath() . '/README.md')) {
            if (!@unlink($this->getWebsitePath() . '/README.md')) {
                throw new Exception('Cannot create the README file');
            }
        }
        if (!@file_put_contents($this->getWebsitePath() . '/README.md', $content)) {
            throw new Exception('Cannot create the README file');
        }
        return 'README file created';
    }

    public function getConfig($key='')
    {
        $configFilePath = $this->getWebsitePath() . '/' . self::PHPOOLE_DIRNAME . '/' . self::CONFIG_FILENAME;
        if (!file_exists($configFilePath)) {
            throw new Exception('Cannot get config file');
        }
        if (!($config = parse_ini_file($configFilePath, true))) {
            throw new Exception('Cannot parse config file');
        }
        if (!empty($key)) {
            if (!array_key_exists($key, $config)) {
                throw new Exception(sprintf('Cannot find %s key in config file', $key));
            }
            return $config[$key];
        }
        return $config;
    }

    public function parseContent($content, $filename, $config)
    {
        $config = $this->getConfig();
        $parser = new MarkdownExtra;
        $parser->code_attr_on_pre = true;
        $parser->predef_urls = array('base_url' => $config['site']['base_url']);
        preg_match('/^<!--(.+)-->(.+)/s', $content, $matches);
        if (!$matches) {
            //throw new Exception(sprintf("Could not parse front matter in %s\n", $filename));
            return array('content' => $contentHtml = $parser->transform($content));
        }
        list($matchesAll, $rawInfo, $rawContent) = $matches;
        $info = parse_ini_string($rawInfo);
        if (isset($info['source']) /* && is valid URL to md file */) {
            if (false === ($rawContent = @file_get_contents($info['source'], false))) {
                throw new Exception(sprintf("Cannot get content from %s\n", $filename));
            }
        }
        $contentHtml = $parser->transform($rawContent);
        return array_merge(
            $info,
            array('content' => $contentHtml)
        );
    }

    public function generate($configToMerge=array())
    {
        $pagesPath = $this->getWebsitePath() . '/' . self::PHPOOLE_DIRNAME . '/' . self::CONTENT_DIRNAME . '/' . self::CONTENT_PAGES_DIRNAME;
        $pages = array();
        $menu['nav'] = array();
        $layoutsPath = $this->getWebsitePath() . '/' . self::PHPOOLE_DIRNAME . '/' . self::LAYOUTS_DIRNAME;
        $config = $this->getConfig();
        if (!empty($configToMerge)) {
            $config = array_replace_recursive($config, $configToMerge);
        }
        // iterate pages
        $pagesIterator = new PhpooleFileIterator($pagesPath, 'md');
        foreach ($pagesIterator as $filePage) {
            $info = $filePage->parse($config)->getData('info');
            $pageIndex = ($pagesIterator->getSubPath() ? $pagesIterator->getSubPath() : 'home');
            $pages[$pageIndex]['title'] = (
                isset($info['title'])
                    && !empty($info['title'])
                ? $info['title']
                : ucfirst($filePage->getBasename('.md'))
            );
            $pages[$pageIndex]['path'] = $pagesIterator->getSubPath();
            $pages[$pageIndex]['basename'] = $filePage->getBasename('.md') . '.html';
            $pages[$pageIndex]['layout'] = (
                isset($info['layout'])
                    && is_file($this->getWebsitePath() . '/' . self::PHPOOLE_DIRNAME . '/layouts' . '/' . $info['layout'] . '.html')
                ? $info['layout'] . '.html'
                : 'default.html'
            );
            if (isset($info['pagination'])) {
                $pages[$pageIndex]['pagination'] = $info['pagination'];
            }
            $pages[$pageIndex]['content'] = $filePage->getData('content');
            // menu
            if (isset($info['menu'])) {
                $menu[$info['menu']][] = (
                    !empty($info['menu'])
                    ? array(
                        'title' => $info['title'],
                        'path'  => $pagesIterator->getSubPath()
                    )
                    : ''
                );
            }
        }
        // sort nav menu
        foreach ($menu['nav'] as $key => $row) {
            $path[$key] = $row['path'];
        }
        array_multisort($path, SORT_ASC, $menu['nav']);
        // rendering
        $tplEngine = $this->tplEngine($layoutsPath);
        $pages = new ArrayObject($pages);
        $pagesIterator = $pages->getIterator();
        $pagesIterator->ksort();
        $pagesIterator->rewind();
        $currentPos = 0;
        $prevPos = '';
        while ($pagesIterator->valid()) {
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
            $rendered = $tplEngine->render($page['layout'], array(
                'site'      => (isset($config['site']) ? $config['site'] : ''),
                'author'    => (isset($config['author']) ? $config['author'] : ''),
                'source'    => (isset($config['deploy']) ? $config['deploy'] : ''),
                'title'     => (isset($page['title']) ? $page['title'] : ''),
                'path'      => (isset($page['path']) ? $page['path'] : ''),
                'content'   => (isset($page['content']) ? $page['content'] : ''),
                'nav'       => (isset($menu['nav']) ? $menu['nav'] : ''),
                'previous'  => (isset($previous) ? $previous : ''),
                'next'      => (isset($next) ? $next : ''),
                'prevTitle' => (isset($prevTitle) ? $prevTitle : ''),
                'nextTitle' => (isset($nextTitle) ? $nextTitle : ''),
            ));
            if (!is_dir($this->getWebsitePath() . '/' . $page['path'])) {
                if (!@mkdir($this->getWebsitePath() . '/' . $page['path'], 0777, true)) {
                    throw new Exception(sprintf('Cannot create %s', $this->getWebsitePath() . '/' . $page['path']));
                }
            }
            if (is_file($this->getWebsitePath() . '/' . ($page['path'] != '' ? $page['path'] . '/' : '') . $page['basename'])) {
                if (!@unlink($this->getWebsitePath() . '/' . ($page['path'] != '' ? $page['path'] . '/' : '') . $page['basename'])) {
                    throw new Exception(sprintf('Cannot delete %s%s', ($page['path'] != '' ? $page['path'] . '/' : ''), $page['basename']));
                }
                $this->setMessage('Delete ' . ($page['path'] != '' ? $page['path'] . '/' : '') . $page['basename']);
            }
            if (!@file_put_contents(sprintf('%s%s', $this->getWebsitePath() . '/' . ($page['path'] != '' ? $page['path'] . '/' : ''), $page['basename']), $rendered)) {
                throw new Exception(sprintf('Cannot write %s%s', ($page['path'] != '' ? $page['path'] . '/' : ''), $page['basename']));
            }
            $this->setMessage(sprintf("Write %s%s", ($page['path'] != '' ? $page['path'] . '/' : ''), $page['basename']));

            $prevPos = $pagesIterator->key();
            $currentPos++;
            $pagesIterator->next();
        }

        foreach ($pages as $page) {
            
        }
        // copy assets
        if (is_dir($this->getWebsitePath() . '/' . self::ASSETS_DIRNAME)) {
            RecursiveRmdir($this->getWebsitePath() . '/' . self::ASSETS_DIRNAME);
        }
        RecursiveCopy($this->getWebsitePath() . '/' . self::PHPOOLE_DIRNAME . '/' . self::ASSETS_DIRNAME, $this->getWebsitePath() . '/' . self::ASSETS_DIRNAME);
        // done
        $this->setMessage('Copy assets directory (and sub)');
        $this->setMessage($this->createReadmeFile());
        return $this->getMessages();
    }

    public function tplEngine($templatesPath)
    {
        $twigLoader = new Twig_Loader_Filesystem($templatesPath);
        $twig = new Twig_Environment($twigLoader, array(
            'autoescape' => false,
            'debug'      => true
        ));
        $twig->addExtension(new Twig_Extension_Debug());
        return $twig;
    }

    public function getPagesTree()
    {
        $pages = array();
        $pagesPath = $this->getWebsitePath() . '/' . self::PHPOOLE_DIRNAME . '/' . self::CONTENT_DIRNAME . '/' . self::CONTENT_PAGES_DIRNAME;
        if (!is_dir($pagesPath)) {
            throw new Exception('Invalid content/pages directory');
        }
        $pages = new FilenameRecursiveTreeIterator(
            new RecursiveDirectoryIterator($pagesPath, RecursiveDirectoryIterator::SKIP_DOTS),
            FilenameRecursiveTreeIterator::SELF_FIRST
        );
        return $pages;
    }

    private function loadPlugins()
    {
        try {
            $configPlugins = $this->getConfig('plugins');
        } catch (Exception $e) {
            $configPlugins = array();
        }
        $pluginsDir = __DIR__ . '/' . self::PLUGINS_DIRNAME;
        if (is_dir($pluginsDir)) {
            $pluginsIterator = new FilesystemIterator($pluginsDir);
            foreach ($pluginsIterator as $plugin) {
                if (array_key_exists($plugin->getBasename(), $configPlugins)
                && $configPlugins[$plugin->getBasename()] == 'disabled') {
                    continue;
                }
                if ($plugin->isDir()) {
                    include_once("$plugin/Plugin.php");
                    $pluginName = $plugin->getBasename();
                    if (class_exists($pluginName)) {
                        $pluginclass = new $pluginName($this->events);
                        if (method_exists($pluginclass, 'preInit')) {
                            $this->getEvents()->attach('init.pre', array($pluginclass, 'preInit'));
                        }
                        if (method_exists($pluginclass, 'postInit')) {
                            $this->getEvents()->attach('init.post', array($pluginclass, 'postInit'));
                        }
                    }
                }
            }
        }
    }
}

/**
 * PHPoole File iterator
 */
class PhpooleFileIterator extends FilterIterator
{
    public function __construct($dirOrIterator = '.', $extension='')
    {
        if (is_string($dirOrIterator)) {
            if (!is_dir($dirOrIterator)) {
                throw new InvalidArgumentException('Expected a valid directory name');
            }
            $dirOrIterator = new RecursiveDirectoryIterator(
                $dirOrIterator,
                RecursiveIteratorIterator::SELF_FIRST
            );
        }
        elseif (!$dirOrIterator instanceof DirectoryIterator) {
            throw new InvalidArgumentException('Expected a DirectoryIterator');
        }
        if ($dirOrIterator instanceof RecursiveIterator) {
            $dirOrIterator = new RecursiveIteratorIterator($dirOrIterator);
        }
        parent::__construct($dirOrIterator);
        $this->setInfoClass('PhpooleFile');
    }

    public function accept()
    {
        $file = $this->getInnerIterator()->current();
        if (!$file instanceof SplFileInfo) {
            return false;
        }
        if (!$file->isFile()) {
            return false;
        }
        if (isset($extension) && is_string($extension)) {
            if ($file->getExtension() == $extension) {
                return true;
            }
        }
        else {
            return true;
        }
    }
}

class PhpooleFile extends SplFileInfo
{
    protected $_data = array();
    protected $_converter = null;

    public function getContents()
    {
        $level = error_reporting(0);
        $content = file_get_contents($this->getRealpath());
        error_reporting($level);
        if (false === $content) {
            $error = error_get_last();
            throw new \RuntimeException($error['message']);
        }
        return $content;
    }

    public function setConverter($config)
    {
        // Markdown only
        $this->_converter = new MarkdownExtra;
        $this->_converter->code_attr_on_pre = true;
        $this->_converter->predef_urls = array('base_url' => $config['site']['base_url']);
        return $this;
    }

    public function getConverter()
    {
        return $this->_converter;
    }

    public function parse($config)
    {
        $this->setConverter($config);
        if (!$this->isReadable()) {
            throw new Exception('Cannot read file');
        }
        if ($this->getConverter() == null) {
            throw new Exception('Converter is no defined');   
        }
        preg_match('/^<!--(.+)-->(.+)/s', $this->getContents(), $matches);
        if (!$matches) {
            $this->setData('content', $this->getConverter->transform($this->getContents()));
            return $this;
        }
        list($matchesAll, $rawInfo, $rawContent) = $matches;
        $info = parse_ini_string($rawInfo);
        if (isset($info['source']) /* && is valid URL to md file */) {
            if (false === ($rawContent = @file_get_contents($info['source'], false))) {
                throw new Exception(sprintf("Cannot get contents from %s\n", $this->getFilename()));
            }
        }
        $this->setData('info', $info);
        $this->setData('content_raw', $rawContent);
        $this->setData('content', $this->getConverter()->transform($rawContent));
        return $this;
    }

    public function setData($key, $value)
    {
        $this->_data[$key] = $value;
        return $this;
    }

    public function getData($key='')
    {
        if ($key == '') {
            return $this->_data;
        }
        if (isset($this->_data[$key])) {
            return $this->_data[$key];
        }
    }
}

/**
 * PHPoole console helper
 */
class PHPooleConsole
{
    protected $console;

    public function __construct($console)
    {
        if (!($console instanceof Zend\Console\Adapter\AdapterInterface)) {
            throw new Exception("Error");
        }
        $this->console = $console;
    }

    public function wlInfo($text)
    {
        echo '[' , $this->console->write('INFO', Color::YELLOW) , ']' . "\t";
        $this->console->writeLine($text);
    }
    public function wlDone($text)
    {
        echo '[' , $this->console->write('DONE', Color::GREEN) , ']' . "\t";
        $this->console->writeLine($text);
    }
    public function wlError($text)
    {
        echo '[' , $this->console->write('ERROR', Color::RED) , ']' . "\t";
        $this->console->writeLine($text);
    }
}

/**
 * PHPoole plugin abstract
 */
abstract class PHPoole_Plugin
{
    const DEBUG = false;

    public function __call($name, $args)
    {
        if (self::DEBUG) {
            printf("[EVENT] %s is not implemented in %s plugin\n", $name, get_class($this));
        }
    }

    public function trace($enabled=self::DEBUG, $e)
    {
        if ($enabled === true) {
            printf(
                '[EVENT] %s/%s %s' . "\n",
                get_class($this),
                $e->getName(),
                json_encode($e->getParams)
            );
        }
    }
}

/**
 * Utils
 */

/**
 * Recursively remove a directory
 *
 * @param string $dirname
 * @param boolean $followSymlinks
 * @return boolean
 */
function RecursiveRmdir($dirname, $followSymlinks=false) {
    if (is_dir($dirname) && !is_link($dirname)) {
        if (!is_writable($dirname)) {
            throw new Exception(sprintf('%s is not writable!', $dirname));
        }
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dirname),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        while ($iterator->valid()) {
            if (!$iterator->isDot()) {
                if (!$iterator->isWritable()) {
                    throw new Exception(sprintf(
                        '%s is not writable!',
                        $iterator->getPathName()
                    ));
                }
                if ($iterator->isLink() && $followLinks === false) {
                    $iterator->next();
                }
                if ($iterator->isFile()) {
                    @unlink($iterator->getPathName());
                }
                elseif ($iterator->isDir()) {
                    @rmdir($iterator->getPathName());
                }
            }
            $iterator->next();
        }
        unset($iterator);
 
        return @rmdir($dirname);
    }
    else {
        throw new Exception(sprintf('%s does not exist!', $dirname));
    }
}

/**
 * Copy a dir, and all its content from source to dest
 */
function RecursiveCopy($source, $dest) {
    if (!is_dir($dest)) {
        @mkdir($dest);
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(
            $source,
            RecursiveDirectoryIterator::SKIP_DOTS
        ),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($iterator as $item) {
        if ($item->isDir()) {
            @mkdir($dest . DS . $iterator->getSubPathName());
        }
        else {
            @copy($item, $dest . DS . $iterator->getSubPathName());
        }
    }
}

/**
 * Execute git commands
 * 
 * @param string working directory
 * @param array git commands
 * @return void
 */
function runGitCmd($wd, $commands)
{
    $cwd = getcwd();
    chdir($wd);
    exec('git config core.autocrlf false');
    foreach ($commands as $cmd) {
        //printf("> git %s\n", $cmd);
        exec(sprintf('git %s', $cmd));
    }
    chdir($cwd);
}

class FilenameRecursiveTreeIterator extends RecursiveTreeIterator
{
    public function current()
    {
        return str_replace(
            $this->getInnerIterator()->current(),
            substr(strrchr($this->getInnerIterator()->current(), DIRECTORY_SEPARATOR), 1),
            parent::current()
        );
    }
}

function isWindows()
{
    return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
}
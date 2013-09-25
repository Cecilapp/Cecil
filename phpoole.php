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

use Zend\Console;
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

define('PHPOOLE_DIRNAME', '_phpoole');
$websitePath = getcwd();

// Defines rules
$rules = array(
    'help|h'     => 'Get PHPoole usage message',
    'init|i-s'   => 'Build a new PHPoole website (with <bootstrap>)',
    'generate|g' => 'Generate static files',
    'serve|s'    => 'Start built-in web server',
    'deploy|d'   => 'Deploy static files',
    'list|l=s'   => 'Lists <pages> or <posts>',
);

// Get and parse console options
try {
    $opts = new Console\Getopt($rules);
    $opts->parse();
} catch (Console\Exception\RuntimeException $e) {
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
        echo 'Invalid directory provided' . PHP_EOL;
        //echo $opts->getUsageMessage();
        exit(2);
    }
    $websitePath = str_replace(DIRECTORY_SEPARATOR, '/', realpath($remainingArgs[0]));
}

// init option
if ($opts->getOption('init')) {
    $layoutType ='';
    printf("Initializing new website in %s\n", $websitePath);
    if ((string)$opts->init == 'bootstrap') {
        $layoutType = 'bootstrap';
    }
    mkInitDir($websitePath);
    mkConfigFile($websitePath . '/' . PHPOOLE_DIRNAME . '/config.ini');
    mkLayoutsDir($websitePath . '/' . PHPOOLE_DIRNAME);
    mkLayoutDefaultFile($websitePath . '/' . PHPOOLE_DIRNAME, 'default.html', $layoutType);
    mkAssetsDir($websitePath . '/' . PHPOOLE_DIRNAME, $layoutType);
    mkContentDir($websitePath . '/'. PHPOOLE_DIRNAME);
    mkContentIndexFile($websitePath . '/' . PHPOOLE_DIRNAME, 'index.md');
    mkRouterFile($websitePath . '/' . PHPOOLE_DIRNAME . '/router.php');
    mkReadmeFile($websitePath . '/README.md');
}

// generate option
if ($opts->getOption('generate')) {
    $pages = array();
    $menu['nav'] = array();
    printf("Generating website in %s\n", $websitePath);
    if (false === ($config = getConfig($websitePath . '/' . PHPOOLE_DIRNAME . '/config.ini'))) {
        echo "[KO] Nothing to generate" . PHP_EOL;
        exit(2);
    }
    if (isset($opts->serve)) {
        $config['site']['base_url'] = 'http://localhost:8000';
        echo "(Youd should re-generate before deploy)" . PHP_EOL;
    }
    $twigLoader = new Twig_Loader_Filesystem($websitePath . '/' . PHPOOLE_DIRNAME . '/layouts');
    $twig = new Twig_Environment($twigLoader, array(
        'autoescape' => false,
        'debug'      => true
    ));
    $twig->addExtension(new Twig_Extension_Debug());
    $pagesPath = $websitePath . '/' . PHPOOLE_DIRNAME . '/content/pages';
    $markdownIterator = new MarkdownFileFilter($pagesPath);
    foreach ($markdownIterator as $filePage) {
        try {
            if (false === ($content = file_get_contents($filePage->getPathname()))) {
                throw new Exception(sprintf('%s not readable', $filePage->getBasename()));
            }
            $page = parseContent($content, $filePage->getFilename(), $config);
        }
        catch (Exception $e) {
            printf("[KO] %s\n", $e->getMessage());
            exit(2);
        }
        $pageIndex = ($markdownIterator->getSubPath() ? $markdownIterator->getSubPath() : 'home');
        $pages[$pageIndex]['layout'] = (
            isset($page['layout'])
                && is_file($websitePath . '/' . PHPOOLE_DIRNAME . '/layouts' . '/' . $page['layout'] . '.html')
            ? $page['layout'] . '.html'
            : 'default.html'
        );
        $pages[$pageIndex]['title'] = (
            isset($page['title'])
                && !empty($page['title'])
            ? $page['title']
            : ucfirst($filePage->getBasename('.md'))
        );
        $pages[$pageIndex]['path'] = $markdownIterator->getSubPath();
        $pages[$pageIndex]['content'] = $page['content'];
        $pages[$pageIndex]['basename'] = $filePage->getBasename('.md') . '.html';
        if (isset($page['menu'])) {
            $menu[$page['menu']][] = (
                !empty($page['menu'])
                ? array(
                    'title' => $page['title'],
                    'path'  => $markdownIterator->getSubPath()
                )
                : ''
            );
        }
    }
    //print_r($pages);
    //print_r($menu);
    foreach ($pages as $key => $page) {
        $rendered = $twig->render($page['layout'], array(
            'site'    => $config['site'],
            'author'  => $config['author'],
            'source'  => $config['deploy'],
            'title'   => $page['title'],
            'path'    => $page['path'],
            'content' => $page['content'],
            'nav'     => $menu['nav'],
        ));
        try {
            if (!is_dir($websitePath . '/' . $page['path'])) {
                if (false === mkdir($websitePath . '/' . $page['path'], 0777, true)) {
                    throw new Exception(sprintf('%s not created', $websitePath . '/' . $page['path']));
                }
            }
            if (is_file($websitePath . '/' . ($page['path'] != '' ? $page['path'] . '/' : '') . $page['basename'])) {
                if (false === unlink($websitePath . '/' . ($page['path'] != '' ? $page['path'] . '/' : '') . $page['basename'])) {
                    throw new Exception(sprintf('%s%s not deleted', ($page['path'] != '' ? $page['path'] . '/' : ''), $page['basename']));
                }
                echo '[OK] delete ' . ($page['path'] != '' ? $page['path'] . '/' : '') . $page['basename'] . PHP_EOL;
            }
            if (false === file_put_contents(sprintf('%s%s', $websitePath . '/' . ($page['path'] != '' ? $page['path'] . '/' : ''), $page['basename']), $rendered)) {
                throw new Exception(sprintf('%s%s not written', ($page['path'] != '' ? $page['path'] . '/' : ''), $page['basename']));
            }
            printf("[OK] write %s%s\n", ($page['path'] != '' ? $page['path'] . '/' : ''), $page['basename']);
        }
        catch (Exception $e) {
            printf("[KO] %s\n", $e->getMessage());
            exit(2);
        }
    }
    if (is_dir($websitePath . '/assets')) {
        RecursiveRmdir($websitePath . '/assets');
    }
    RecursiveCopy($websitePath . '/' . PHPOOLE_DIRNAME . '/assets', $websitePath . '/assets');
    printf("[OK] copy assets directory\n");
}

// serve option
if ($opts->getOption('serve')) {
    if (version_compare(PHP_VERSION, '5.4.0', '<')) {
        echo 'PHP 5.4+ required to run built-in server (your version: ' . PHP_VERSION . ')' . PHP_EOL;
        exit(2);
    }
    if (!is_file(sprintf('%s/%s/router.php', $websitePath, PHPOOLE_DIRNAME))) {
        echo 'Router not found' . PHP_EOL;
        exit(2);
    }
    printf("Start server http://%s:%d\n", 'localhost', '8000');
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
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
    $config = getConfig($websitePath . '/' . PHPOOLE_DIRNAME . '/config.ini');
    if (
        !isset($config['deploy']['repository'])
            && !isset($config['deploy']['branch'])
        ) {
        echo '[KO] no repository in config.ini' . PHP_EOL;
        exit(2);
    }
    else {
        $repoUrl = $config['deploy']['repository'];
        $repoBranch = $config['deploy']['branch'];
    }
    $deployDir = $websitePath . '/../.' . basename($websitePath);
    if (is_dir($deployDir)) {
        echo 'Deploying files to GitHub...' . PHP_EOL;
        $deployIterator = new FilesystemIterator($deployDir);
        foreach ($deployIterator as $deployFile) {
            if ($deployFile->isFile()) {
                unlink($deployFile->getPathname());
            }
            if ($deployFile->isDir() && $deployFile->getFilename() != '.git') {
                RecursiveRmDir($deployFile->getPathname());
            }
        }
        RecursiveCopy($websitePath, $deployDir);
        $updateRepoCmd = array(
            'add -A',
            'commit -m "Update ' . $repoBranch . ' via PHPoole"',
            'push github ' . $repoBranch . ' --force'
        );
        runGitCmd($deployDir, $updateRepoCmd);
    }
    else {
        echo 'Setting up GitHub deployment...' . PHP_EOL;
        mkdir($deployDir);
        RecursiveCopy($websitePath, $deployDir);
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

// list option
if ($opts->getOption('list')) {
    if (isset($opts->list) && $opts->list == 'pages') {
        printf("List pages in %s\n", $websitePath);
        if (!is_dir($websitePath . '/' . PHPOOLE_DIRNAME . '/content/pages')) {
            echo 'Invalid content/pages directory' . PHP_EOL;
            echo $opts->getUsageMessage();
            exit(2);
        }
        $contentIterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($websitePath . '/' . PHPOOLE_DIRNAME . '/content/pages'),
            RecursiveIteratorIterator::CHILD_FIRST
        );    
        foreach($contentIterator as $file) {
            if ($file->isFile()) {
                printf("- %s%s\n", ($contentIterator->getSubPath() != '' ? $contentIterator->getSubPath() . '/' : ''), $file->getFilename());
            }
        }
    }
    else if (isset($opts->list) && $opts->list == 'posts') {
        printf("List posts in %s\n", $websitePath);
        // @todo todo! :-)
    }
    else {
        echo $opts->getUsageMessage();
        exit(2);
    }
}


/**
 * PHPoole helpers
 */

function mkInitDir($path, $force = false) {
    try {
        if (is_dir($path . '/' . PHPOOLE_DIRNAME)) {
            if ($force) {
                RecursiveRmdir($path . '/' . PHPOOLE_DIRNAME);
            }
            else {
                throw new Exception(sprintf('%s already exist in %s', PHPOOLE_DIRNAME, basename($path)));
            }
        }
        if (false === mkdir($path . '/' . PHPOOLE_DIRNAME)) {
            throw new Exception(sprintf('%s not created', $path));
        }
        printf("[OK] create %s\n", PHPOOLE_DIRNAME);
    }  
    catch (Exception $e) {
        printf("[KO] %s\n", $e->getMessage());
        exit(2);
    }
}

function mkConfigFile($filePath) {
    $content = <<<'EOT'
[site]
name        = "PHPoole"
baseline    = "Light and easy static website generator!"
description = "PHPoole is a simple static website/weblog generator written in PHP. It parses your content written with Markdown, merge it with layouts and generates static HTML files."
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
    try {
        if (false === file_put_contents($filePath, $content)) {
            throw new Exception(sprintf('%s not created', basename($filePath)));
        }
        printf("[OK] create %s/%s\n", PHPOOLE_DIRNAME, basename($filePath));
    }
    catch (Exception $e) {
        printf("[KO] %s\n", $e->getMessage());
        exit(2);
    }
}

function mkLayoutsDir($path) {
    try {
        if (false === mkdir(sprintf('%s/layouts', $path))) {
            throw new Exception(sprintf('%s/layouts not created', $path));
        }
        printf("[OK] create %s/layouts\n", PHPOOLE_DIRNAME);
    }
    catch (Exception $e) {
        printf("[KO] %s\n", $e->getMessage());
        exit(2);
    }
}

function mkLayoutDefaultFile($path, $filename, $type='') {
    $subdir = 'layouts';
    if ($type == 'bootstrap') {
        $content = <<<'EOT'
<!DOCTYPE html>
<html lang="{{ site.language }}">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{{ site.description }}">
    <meta name="author" content="{{ author.name }}">
    <title>{{ site.name }} - {{ title|title }}</title>
    <link href="{{ site.base_url }}/assets/css/bootstrap.min.css" rel="stylesheet">
    <style type="text/css">
      html, body {height: 100%;}
      #wrap {min-height: 100%;height: auto !important;height: 100%;margin: 0 auto -60px;padding: 0 0 60px;}
      #footer {height: 60px;background-color: #f5f5f5;}
      #wrap > .container {padding: 60px 15px 0;}
      .container .credit {margin: 20px 0;}
      #footer > .container {padding-left: 15px;padding-right: 15px;}
      code {font-size: 80%;}
    </style>
    <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
      <script src="http://getbootstrap.com/assets/js/html5shiv.js"></script>
      <script src="http://getbootstrap.com/assets/js/respond.min.js"></script>
    <![endif]-->
  <body>
    {% if source.repository %}
    <a href="{{ source.repository }}"><img style="position: absolute; top: 0; right: 0; border: 0; z-index: 9999;" src="https://s3.amazonaws.com/github/ribbons/forkme_right_gray_6d6d6d.png" alt="Fork me on GitHub"></a>
    {% endif %}
    <div id="wrap">
      <div class="navbar navbar-default navbar-fixed-top">
        <div class="container">
          <div class="navbar-header">
            {% if nav %}
            <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
              {% for item in nav %}
              <span class="icon-bar"></span>
              {% endfor %}
            </button>
            {% endif %}
            <a class="navbar-brand" href="{{ site.base_url }}">{{ site.name}}</a>
          </div>
          <div class="collapse navbar-collapse">
            <ul class="nav navbar-nav">
              {% for item in nav %}
              <li {% if item.path == path %}class="active"{% endif %}><a href="{{ site.base_url }}{% if item.path != '' %}/{{ item.path }}{% endif %}">{{ item.title|e }}</a></li>
              {% endfor %}
            </ul>
          </div><!--/.nav-collapse -->
        </div>
      </div>
      <!-- Begin page content -->
      <div class="container">
        <div class="page-header">
          <h1>{{ site.name}}</h1>
          <p class="lead"><em>{{ site.baseline }}</em></p>
        </div>
        {{ content }}
      </div>
    </div>
    <div id="footer">
      <div class="container">
        <p class="text-muted credit">&copy; <a href="{{ author.home }}">{{ author.name }}</a> {{ 'now'|date('Y') }} - Powered by <a href="http://narno.org/PHPoole">PHPoole</a></p>
      </div>
    </div>
    <script src="{{ site.base_url }}/assets/js/jquery.min.js"></script>
    <script src="{{ site.base_url }}/assets/js/bootstrap.min.js"></script>
  </body>
</html>
EOT;
    }
    else {
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
    }
    try {
        if (false === file_put_contents("$path/$subdir/$filename", $content)) {
            throw new Exception(sprintf('%s/%s not created', $subdir, basename($filename)));
        }
        printf("[OK] create %s/%s/%s\n", PHPOOLE_DIRNAME, $subdir, $filename);
    }
    catch (Exception $e) {
        printf("[KO] %s\n", $e->getMessage());
        exit(2);
    }
}

function mkAssetsDir($path, $type='') {
    $subDirList = array(
        'assets',
        'assets/css',
        'assets/img',
        'assets/js',
    );
    try {
        foreach ($subDirList as $subDir) {
            if (false === mkdir(sprintf('%s/%s', $path, $subDir))) {
                throw new Exception(sprintf('%s/%s not created', $path, $subDir));
            }
            printf("[OK] create %s/%s\n", PHPOOLE_DIRNAME, $subDir);
        }
    }
    catch (Exception $e) {
        printf("[KO] %s\n", $e->getMessage());
        exit(2);
    }
    if ($type == 'bootstrap') {
        echo 'Downloading Twitter Bootstrap assets files and jQuery script...' . PHP_EOL;
        exec(sprintf('curl %s > %s/assets/css/bootstrap.min.css', 'https://raw.github.com/twbs/bootstrap/v3.0.0/dist/css/bootstrap.min.css', $path));
        exec(sprintf('curl %s > %s/assets/js/bootstrap.min.js', 'https://raw.github.com/twbs/bootstrap/v3.0.0/dist/js/bootstrap.min.js', $path));
        exec(sprintf('curl %s > %s/assets/js/jquery.min.js', 'http://code.jquery.com/jquery-2.0.3.min.js', $path));
    }
}

function mkContentDir($path) {
    $subDirList = array(
        'content',
        'content/posts',
        'content/pages',
    );
    try {
        foreach ($subDirList as $subDir) {
            if (false === mkdir(sprintf('%s/%s', $path, $subDir))) {
                throw new Exception(sprintf('%s/%s not created', $path, $subDir));
            }
            printf("[OK] create %s/%s\n", PHPOOLE_DIRNAME, $subDir);
        }
    }
    catch (Exception $e) {
        printf("[KO] %s\n", $e->getMessage());
        exit(2);
    }
}

function mkContentIndexFile($path, $filename) {
    $subdir = 'content/pages';
    $content = <<<'EOT'
<!--
title = Home
layout = default
menu = nav
-->
Welcome!
========

PHPoole is a simple static website/weblog generator written in PHP.
It parses your content written with Markdown, merge it with layouts and generates static HTML files.

PHPoole = [PHP](http://www.php.net) + [Poole](http://en.wikipedia.org/wiki/Strange_Case_of_Dr_Jekyll_and_Mr_Hyde#Mr._Poole)

Go to the [dedicated website](http://narno.org/PHPoole) for more details.
EOT;
    try {
        if (false === file_put_contents("$path/$subdir/$filename", $content)) {
            throw new Exception(sprintf('%s/%s not created', $subdir, basename($filename)));
        }
        printf("[OK] create %s/%s/%s\n", PHPOOLE_DIRNAME, $subdir, $filename);
    }
    catch (Exception $e) {
        printf("[KO] %s\n", $e->getMessage());
        exit(2);
    }
}

function mkRouterFile($filePath) {
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
    try {
        if (is_file($filePath)) {
            unlink($filePath);
        }
        if (false === file_put_contents($filePath, $content)) {
            throw new Exception(sprintf('%s not created', basename($filename)));
        }
        printf("[OK] create %s/%s\n", PHPOOLE_DIRNAME, basename($filePath));
    }
    catch (Exception $e) {
        printf("[KO] %s\n", $e->getMessage());
        exit(2);
    }
}

function mkReadmeFile($filePath) {
    $content = <<<'EOT'
Powered by [PHPoole](http://narno.org/PHPoole/).
EOT;
    try {
        if (is_file($filePath)) {
            unlink($filePath);
        }
        if (false === file_put_contents($filePath, $content)) {
            throw new Exception(sprintf('%s not created', basename($filename)));
        }
        printf("[OK] create %s/%s\n", PHPOOLE_DIRNAME, basename($filePath));
    }
    catch (Exception $e) {
        printf("[KO] %s\n", $e->getMessage());
        exit(2);
    }
}

function parseContent($content, $filename, $config) {
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
    $contentHtml = $parser->transform($rawContent);
    return array_merge(
        $info,
        array('content' => $contentHtml)
    );
}

function getConfig($filename) {
    return (file_exists($filename) ? parse_ini_file($filename, true) : false);
}

function runGitCmd($wd, $commands) {
    chdir($wd);
    exec('git config core.autocrlf false');
    foreach ($commands as $cmd) {
        printf("> git %s\n", $cmd);
        exec(sprintf('git %s', $cmd));
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
                    unlink($iterator->getPathName());
                }
                elseif ($iterator->isDir()) {
                    rmdir($iterator->getPathName());
                }
            }
            $iterator->next();
        }
        unset($iterator);
 
        return rmdir($dirname);
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
        mkdir($dest);
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($iterator as $item) {
        if ($item->isDir()) {
            mkdir($dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
        }
        else {
            copy($item, $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
        }
    }
}

/**
 * Markdown file iterator
 */
class MarkdownFileFilter extends FilterIterator
{
    public function __construct($dirOrIterator = '.')
    {
        if (is_string($dirOrIterator)) {
            if (!is_dir($dirOrIterator)) {
                throw new Exception\InvalidArgumentException('Expected a valid directory name');
            }
            $dirOrIterator = new RecursiveDirectoryIterator($dirOrIterator, RecursiveDirectoryIterator::FOLLOW_SYMLINKS);
        }
        elseif (!$dirOrIterator instanceof DirectoryIterator) {
            throw new Exception\InvalidArgumentException('Expected a DirectoryIterator');
        }
        if ($dirOrIterator instanceof RecursiveIterator) {
            $dirOrIterator = new RecursiveIteratorIterator($dirOrIterator);
        }
        parent::__construct($dirOrIterator);
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
        if ($file->getExtension() == 'md') {
            return true;
        }
    }
}
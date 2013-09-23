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
use Michelf\Markdown;

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
    'init|i'     => 'Build a new PHPoole website',
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
    printf("Initializing new website in %s\n", $websitePath);
    mkInitDir($websitePath);
    mkConfigFile($websitePath . '/' . PHPOOLE_DIRNAME . '/config.ini');
    mkLayoutsDir($websitePath . '/' . PHPOOLE_DIRNAME);
    mkLayoutBaseFile($websitePath . '/' . PHPOOLE_DIRNAME, 'base.html');
    mkAssetsDir($websitePath . '/' . PHPOOLE_DIRNAME);
    mkContentDir($websitePath . '/'. PHPOOLE_DIRNAME);
    mkContentIndexFile($websitePath . '/' . PHPOOLE_DIRNAME, 'index.md');
    mkRouterFile($websitePath . '/' . PHPOOLE_DIRNAME . '/router.php');
}

// generate option
if ($opts->getOption('generate')) {
    printf("Generating website in %s\n", $websitePath);
    $config = getConfig($websitePath . '/' . PHPOOLE_DIRNAME . '/config.ini');
    $twigLoader = new Twig_Loader_Filesystem($websitePath . '/' . PHPOOLE_DIRNAME . '/layouts');
    $twig = new Twig_Environment($twigLoader, array('autoescape' => false));
    $pagesPath = $websitePath . '/' . PHPOOLE_DIRNAME . '/content/pages';
    $markdownIterator = new MarkdownFileFilter($pagesPath);
    // @todo work in 2 steps:
    //   1. get data: number of pages, use in menu, etc.
    //   2. use data to build pages
    foreach ($markdownIterator as $filePage) {
        try {
            if (false === ($content = file_get_contents($filePage->getPathname()))) {
                throw new Exception(sprintf('%s not readable', $filePage->getBasename()));
            }
            $page = parseContent($content, $filePage->getFilename());
        }
        catch (Exception $e) {
            printf("[KO] %s\n", $e->getMessage());
            exit(2);
        }
        if (isset($page['title'])) {
            $title = $page['title'];
        }
        else {
            $title = 'PHPoole';
        }
        if (
            isset($page['layout'])
            && is_file($websitePath . '/' . PHPOOLE_DIRNAME . '/layouts' . '/' . $page['layout'] . '.html')
        ) {
            $layout = $page['layout'] . '.html';
        }
        else {
            $layout = 'base.html';
        }
        $rendered = $twig->render($layout, array(
            'site'    => $config['site'],
            'author'  => $config['author'],
            'title'   => $title,
            'content' => $page['content']
        ));
        try {
            if (!is_dir($websitePath . '/' . $markdownIterator->getSubPath())) {
                if (false === mkdir($websitePath . '/' . $markdownIterator->getSubPath(), 0777, true)) {
                    throw new Exception(sprintf('%s not created', $websitePath . '/' . $markdownIterator->getSubPath()));
                }
            }
            if (is_file($websitePath . '/' . ($markdownIterator->getSubPath() != '' ? $markdownIterator->getSubPath() . '/' : '') . $filePage->getBasename('.md') . '.html')) {
                if (false === unlink($websitePath . '/' . ($markdownIterator->getSubPath() != '' ? $markdownIterator->getSubPath() . '/' : '') . $filePage->getBasename('.md') . '.html')) {
                    throw new Exception(sprintf('%s%s.html not deleted', ($markdownIterator->getSubPath() != '' ? $markdownIterator->getSubPath() . '/' : ''), $filePage->getBasename('.md')));
                }
                echo '[OK] delete ' . ($markdownIterator->getSubPath() != '' ? $markdownIterator->getSubPath() . '/' : '') . $filePage->getBasename('.md') . '.html' . PHP_EOL;
            }
            if (false === file_put_contents(sprintf('%s%s.html', $websitePath . '/' . ($markdownIterator->getSubPath() != '' ? $markdownIterator->getSubPath() . '/' : ''), $filePage->getBasename('.md')), $rendered)) {
                throw new Exception(sprintf('%s%s.html not written', ($markdownIterator->getSubPath() != '' ? $markdownIterator->getSubPath() . '/' : ''), $filePage->getBasename('.md')));
            }
            printf("[OK] write %s%s.html\n", ($markdownIterator->getSubPath() != '' ? $markdownIterator->getSubPath() . '/' : ''), $filePage->getBasename('.md'));
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
    if (!isset($config['deploy']['repository'])) {
        echo '[KO] no repository in config.ini' . PHP_EOL;
        exit(2);
    }
    else {
        $repoUrl = $config['deploy']['repository'];
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
            //'commit -m "Site updated: ' . date('Y-m-d H:i:s') . '"',
            'commit -m "Update gh-pages via PHPoole"',
            'push github gh-pages --force'
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
            //'commit -m "First commit"',
            'commit -m "Create gh-pages via PHPoole"',
            'branch -M gh-pages',
            'remote add github ' . $repoUrl,
            'push github gh-pages --force'
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
baseline    = "Light and easy website generator!"
description = "PHPoole is a simple static website/weblog generator written in PHP. It parses your content written with Markdown, merge it with layouts and generates static HTML files."
base_url    = "http://localhost:8000"
language    = "en"
[author]
name  = "Arnaud Ligny"
email = "arnaud+phpoole@ligny.org"
home  = "http://narno.org"
[deploy]
repository = "https://github.com/Narno/PHPoole-demo.git"
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

function mkLayoutBaseFile($path, $filename) {
    $subdir = 'layouts';
    $content = <<<'EOT'
<!DOCTYPE html>
<html lang="{{ site.language }}">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{{ site.description }}">
    <meta name="author" content="{{ author.name }}">
    <link rel="shortcut icon" href="http://getbootstrap.com/assets/ico/favicon.png">
    <title>{{ site.name}} - {{ title }}</title>
    <link href="http://getbootstrap.com/dist/css/bootstrap.css" rel="stylesheet">
    <!-- Custom styles for this template -->
    <link href="http://getbootstrap.com/examples/sticky-footer-navbar/sticky-footer-navbar.css" rel="stylesheet">
    <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
      <script src="http://getbootstrap.com/assets/js/html5shiv.js"></script>
      <script src="http://getbootstrap.com/assets/js/respond.min.js"></script>
    <![endif]-->
   <body>
    <div id="wrap">
      <div class="navbar navbar-default navbar-fixed-top">
        <div class="container">
          <div class="navbar-header">
            <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
              <span class="icon-bar"></span>
              <span class="icon-bar"></span>
              <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="{{ site.base_url }}">{{ site.name}}</a>
          </div>
          <div class="collapse navbar-collapse">
            <ul class="nav navbar-nav">
              <li class="active"><a href="#">Home</a></li>
              <li><a href="#about">About</a></li>
              <li><a href="#contact">Contact</a></li>
            </ul>
          </div><!--/.nav-collapse -->
        </div>
      </div>
      <!-- Begin page content -->
      <div class="container">
        {{ content }}
      </div>
    </div>
    <div id="footer">
      <div class="container">
        <p class="text-muted credit">Powered by <a href="http://narno.org/PHPoole">PHPoole</a>, coded by <a href="{{ author.home }}">{{ author.name }}</a>.</p>
      </div>
    </div>
    <script src="http://getbootstrap.com/assets/js/jquery.js"></script>
    <script src="http://getbootstrap.com/dist/js/bootstrap.min.js"></script>
  </body>
</html>
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

function mkAssetsDir($path) {
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
title = PHPoole
layout = base
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

function parseContent($content, $filename) {
    preg_match('/^<!--(.+)-->(.+)/s', $content, $matches);
    if (!$matches) {
        throw new Exception(sprintf("Could not parse front matter in %s\n", $filename));
    }
    list($matchesAll, $rawInfo, $rawContent) = $matches;
    $info = parse_ini_string($rawInfo);
    $contentHtml = Markdown::defaultTransform($rawContent);
    return array_merge(
        $info,
        array('content' => $contentHtml)
    );
}

function getConfig($filename) {
    return parse_ini_file($filename, true);
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
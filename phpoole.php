<?php

use Zend\Console;
use Zend\Loader\StandardAutoloader;

// Includes ZF2 lib
$zfLibraryPath = __DIR__ . '/vendor';
if (is_dir($zfLibraryPath)) {
    // Try to load StandardAutoloader from library
    if (false === include($zfLibraryPath . '/Zend/Loader/StandardAutoloader.php')) {
        echo 'Unable to locate autoloader via library; aborting' . PHP_EOL;
        exit(2);
    }
} else {
    // Try to load StandardAutoloader from include_path
    if (false === include('Zend/Loader/StandardAutoloader.php')) {
        echo 'Unable to locate autoloader via include_path; aborting' . PHP_EOL;
        exit(2);
    }
}

// Setup autoloading
$loader = new StandardAutoloader(array('autoregister_zf' => true));
$loader->register();

$pwd = getcwd();

// Defines rules
$rules = array(
    'help|h'     => 'Get usage message',
    'init'       => 'Build all files for a new website',
    'generate|g' => 'Generate static website',
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
if ($opts->getOption('h')) {
    echo $opts->getUsageMessage();
    exit(0);
}

// init option
if ($opts->getOption('init')) {
    echo 'init...' . PHP_EOL;
    Init($pwd);
    echo 'directory ".phpoole/" created...' . PHP_EOL;
    MakeConfigFile($pwd . '/.phpoole/config.ini');
    echo 'file ".phpoole/config.ini" created...' . PHP_EOL;
    mkdir($pwd . '/.phpoole/layouts');
    MakeConfigFile($pwd . '/.phpoole/layouts/base.php');
    echo 'file ".phpoole/layouts/base.php" created...' . PHP_EOL;
    echo 'done.' . PHP_EOL;
    exit(0);
}

// Displays usage message by default
echo $opts->getUsageMessage();
exit(2);


/**
 * PHPoole helpers
 */

function Init($path) {
    if (is_dir($path . '/.phpoole')) {
        RecursiveRmdir($path . '/.phpoole');
    }
    mkdir($path . '/.phpoole');
}

function MakeConfigFile($filePath) {
    $content = <<<EOL
[site]
title        = "PHPoole"
baseline     = "PHPoole is a simple static website/weblog generator written in PHP."
description  = "PHPoole is (will be!) a simple static website/weblog generator written in PHP. It parses your content written with Markdown, merge it with layouts and generates static HTML files."
base_url     = "http://localhost/PHPoole"
language     = "en_US"

[author]
name  = "Arnaud Ligny"
email = "arnaud+phpoole@ligny.org"
home  = "http://narno.org"
EOL;
    if (is_file($filePath)) {
        unlink($filePath);
    }
    file_put_contents($filePath, $content);
}

function MakeLayoutBaseFile($filePath) {
    $content = <<<EOL
<!DOCTYPE html>
<!--[if IE 8]> 				 <html class="no-js lt-ie9" lang="en" > <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js" lang="en" > <!--<![endif]-->
<head>
	<meta charset="utf-8">
  <meta name="viewport" content="width=device-width">
  <title>{{ site.title }}</title>
</head>
<body>
  <?php print $content ?>
</body>
</html>
EOL;
    if (is_file($filePath)) {
        unlink($filePath);
    }
    file_put_contents($filePath, $content);
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
function RecursiveRmdir($dirname, $followSymlinks=false)
{
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
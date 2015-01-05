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

error_reporting(E_ALL ^ E_NOTICE);
date_default_timezone_set("UTC");
$version = '2.0.0-dev';
$phpVersion = '5.4.0';

use Zend\Console\Console;
use ZF\Console\Application;
use PHPoole\PHPoole;

if (file_exists(__DIR__ . '/../composer.json')) {
    @$composer = json_decode(file_get_contents(__DIR__ . '/../composer.json'), true);
    if (isset($composer['version'])) {
        $version = $composer['version'];
    }
}
define('VERSION', $version);

if (version_compare(PHP_VERSION, $phpVersion, '<')) {
    print('PHP 5.4+ required (your version: ' . PHP_VERSION . ')' . "\n");
    exit(2);
}

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    $loader = include __DIR__ . '/../vendor/autoload.php';
} else {
    echo 'Run the following commands:' . PHP_EOL;
    if (!file_exists(__DIR__ . '/../composer.json')) {
        echo 'curl https://raw.github.com/Narno/PHPoole/master/composer.json > composer.json' . PHP_EOL;
    }
    if (!file_exists(__DIR__ . '/../composer.phar')) {
        echo 'curl -s http://getcomposer.org/installer | php' . PHP_EOL;
    }  
    echo 'php composer.phar install' . PHP_EOL;
    exit(2);
}

$config = include __DIR__ . '/../config/routes.php';
$application = new Application(
    $config['name'],
    $config['version'],
    $config['routes'],
    Console::getInstance()
);

$application->setBanner(function ($console) {
    $script = (Phar::running(false) == '') ? __FILE__ : Phar::running(false);
    $console->writeLine('PHPoole, Light and easy static website generator!');
    $console->writeLine('');
    $console->writeLine('Usage:', \Zend\Console\ColorInterface::GREEN);
    $console->writeLine(' php ' . basename($script) . ' command [options]');
    $console->writeLine('');
});
$application->setFooter("\n" . 'Created by Arnaud Ligny');

$exit = $application->run();
exit($exit);
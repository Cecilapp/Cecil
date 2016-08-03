<?php
/**
 * PHPoole is a light and easy static website generator written in PHP.
 *
 * @see http://phpoole.narno.org
 *
 * @author Arnaud Ligny <arnaud@ligny.org>
 * @license The MIT License (MIT)
 *
 * Copyright (c) 2013-2014 Arnaud Ligny
 */
error_reporting(E_ALL ^ E_NOTICE);
date_default_timezone_set('UTC');

$name = 'PHPoole';
$version = '2.0.0-dev';
$phpVersion = '5.4.0';

use Zend\Console\Console;
use ZF\Console\Application;

// fetch app version from composer file if exists
if (file_exists(__DIR__.'/../composer.json')) {
    @$composer = json_decode(file_get_contents(__DIR__.'/../composer.json'), true);
    if (isset($composer['version'])) {
        $version = $composer['version'];
    }
}

// compare PHP version
if (version_compare(PHP_VERSION, $phpVersion, '<')) {
    echo 'PHP 5.4+ required (your version: '.PHP_VERSION.')'."\n";
    exit(2);
}

// autoload from Phar vendor
if (Phar::running(false) != '') {
    require_once Phar::running(true).'/vendor/autoload.php';
// autoload from local vendor
} else {
    if (file_exists(__DIR__.'/../vendor/autoload.php')) {
        require_once __DIR__.'/../vendor/autoload.php';
    } else {
        echo 'Run the following commands:'.PHP_EOL;
        if (!file_exists(__DIR__.'/../composer.phar')) {
            echo 'curl -s http://getcomposer.org/installer | php'.PHP_EOL;
        }
        //if (!file_exists(__DIR__ . '/../composer.json')) {
        //    echo 'curl https://raw.github.com/Narno/PHPoole/master/composer.json > composer.json' . PHP_EOL;
        //}
        echo 'php composer.phar require narno/phpoole';
        echo 'php composer.phar install'.PHP_EOL;
        exit(2);
    }
}

$application = new Application(
    $name,
    $version,
    include __DIR__.'/../config/routes.php',
    Console::getInstance()
);

$application->setBanner(function ($console) {
    $script = (Phar::running(false) == '') ? __FILE__ : Phar::running(false);
    $console->write('    ____  __  ______              __   
   / __ \/ / / / __ \____  ____  / /__ 
  / /_/ / /_/ / /_/ / __ \/ __ \/ / _ \
 / ____/ __  / ____/ /_/ / /_/ / /  __/
/_/   /_/ /_/_/    \____/\____/_/\___/ 
');
    $console->writeLine('Light and easy static website generator!');
    $console->writeLine('');
    $console->writeLine('Usage:', \Zend\Console\ColorInterface::GREEN);
    $console->writeLine(' php '.basename($script).' command [options]');
    $console->writeLine('');
});
$application->setFooter(function ($console) {
    $console->writeLine('');
    $console->writeLine('Created by Arnaud Ligny');
    $console->writeLine('');
});

$exit = $application->run();
exit($exit);

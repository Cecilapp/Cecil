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

use Zend\Console\Console;
use ZF\Console\Application;
use PHPoole\PHPoole;

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    include __DIR__ . '/../vendor/autoload.php';
}

$config = include __DIR__ . '/../config/routes.php';
$application = new Application(
    $config['name'],
    $config['version'],
    $config['routes'],
    Console::getInstance()
);

$application->setBanner(function ($console) {
    $console->writeLine('PHPoole, Light and easy static website generator!');
    $console->writeLine('');
    $console->writeLine('Usage:', \Zend\Console\ColorInterface::GREEN);
    $console->writeLine(' ' . basename(__FILE__) . ' command [options]');
    $console->writeLine('');
});
$application->setFooter("\n" . 'Created by Arnaud Ligny');

$exit = $application->run();
exit($exit);
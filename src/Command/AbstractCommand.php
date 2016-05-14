<?php
/*
 * This file is part of the PHPoole package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Command;

use PHPoole\PHPoole;
use Symfony\Component\Yaml\Yaml;
use Zend\Console\Adapter\AdapterInterface as Console;
use Zend\Console\ColorInterface as Color;
use ZF\Console\Route;

abstract class AbstractCommand
{
    /**
     * @var Console
     */
    protected $console;

    /**
     * @var Route
     */
    protected $route;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var PHPoole
     */
    protected $phpoole;

    /**
     * Start command processing.
     *
     * @param Route   $route
     * @param Console $console
     *
     * @return mixed
     */
    public function __invoke(Route $route, Console $console)
    {
        $this->route = $route;
        $this->console = $console;

        $this->path = realpath($this->route->getMatchedParam('path', getcwd()));
        if (!is_dir($this->path)) {
            $this->wlError('Invalid directory provided!');
            exit(2);
        }
        $this->path = str_replace(DIRECTORY_SEPARATOR, '/', $this->path);
        if (!file_exists($this->path.'/phpoole.yml')) {
            $this->wlError('Config file (phpoole.yml) not found!');
            exit(2);
        }

        // Instantiate PHPoole library
        try {
            $options = Yaml::parse(file_get_contents($this->path.'/phpoole.yml'));
            $this->phpoole = new PHPoole($options);
            $this->phpoole->setSourceDir($this->path);
            $this->phpoole->setDestDir($this->path);
        } catch (\Exception $e) {
            $this->wlError($e->getMessage());
            exit(2);
        }

        return $this->processCommand();
    }

    /**
     * Process the command.
     */
    abstract public function processCommand();

    /**
     * @return Console
     */
    public function getConsole()
    {
        return $this->console;
    }

    /**
     * @return Route
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return PHPoole
     */
    public function getPhpoole()
    {
        return $this->phpoole;
    }

    /**
     * @param $text
     */
    public function wlAnnonce($text)
    {
        $this->console->writeLine($text, Color::WHITE);
    }

    /**
     * @param $text
     */
    public function wlDone($text)
    {
        $this->console->writeLine($text, Color::GREEN);
    }

    /**
     * @param $text
     */
    public function wlAlert($text)
    {
        $this->console->writeLine($text, Color::YELLOW);
    }

    /**
     * @param $text
     */
    public function wlError($text)
    {
        $this->console->writeLine($text, Color::RED);
    }
}

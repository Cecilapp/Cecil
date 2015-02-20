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

use Zend\Console\Adapter\AdapterInterface as Console;
use Zend\Console\ColorInterface as Color;
use ZF\Console\Route;
use PHPoole\PHPoole;

abstract class AbstractCommand
{

    /**
     * @var Console
     */
    protected $_console;

    /**
     * @var Route
     */
    protected $_route;

    /**
     * @var string
     */
    protected $_path;

    /**
     * @var PHPoole
     */
    protected $_phpoole;

    /**
     * Start command processing
     *
     * @param Route   $route
     * @param Console $console
     * @return mixed
     */
    public function __invoke(Route $route, Console $console)
    {
        $this->_route   = $route;
        $this->_console = $console;

        $this->_path = realpath($this->_route->getMatchedParam('path', getcwd()));
        if (!is_dir($this->_path)) {
            $this->wlError('Invalid directory provided!');
            exit(2);
        }
        $this->_path = str_replace(DIRECTORY_SEPARATOR, '/', $this->_path);

        // Instantiate PHPoole library
        try {
            $this->_phpoole = new PHPoole($this->_path);
        } catch (\Exception $e) {
            $this->wlError($e->getMessage());
            exit(2);
        }

        return $this->processCommand();
    }

    /**
     * Process the command
     */
    abstract public function processCommand();

    /**
     * @return Console
     */
    public function getConsole()
    {
        return $this->_console;
    }

    /**
     * @return Route
     */
    public function getRoute()
    {
        return $this->_route;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->_path;
    }

    /**
     * @return PHPoole
     */
    public function getPhpoole()
    {
        return $this->_phpoole;
    }

    /**
     * @param $text
     */
    public function wlAnnonce($text)
    {
        $this->_console->writeLine($text, Color::WHITE);
    }

    /**
     * @param $text
     */
    public function wlDone($text)
    {
        $this->_console->writeLine($text, Color::GREEN);
    }

    /**
     * @param $text
     */
    public function wlAlert($text)
    {
        $this->_console->writeLine($text, Color::YELLOW);
    }

    /**
     * @param $text
     */
    public function wlError($text)
    {
        $this->_console->writeLine($text, Color::RED);
    }
}
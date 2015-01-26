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

        // Instanciate PHPoole
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
     *
     * @return integer
     */
    abstract public function processCommand();

    public function wlAnnonce($text)
    {
        $this->_console->writeLine($text, Color::WHITE);
    }
    public function wlDone($text)
    {
        //$this->_console->write(' DONE ', Color::WHITE, Color::GREEN);
        //$this->_console->write(' ');
        $this->_console->writeLine($text, Color::GREEN);
    }
    public function wlAlert($text)
    {
        //$this->_console->write(' ALER ', Color::WHITE, Color::YELLOW);
        //$this->_console->write(' ');
        $this->_console->writeLine($text, Color::YELLOW);
    }
    public function wlError($text)
    {
        //$this->_console->write(' ERRO ', Color::WHITE, Color::RED);
        //$this->_console->write(' ');
        $this->_console->writeLine($text, Color::RED);
    }
}
<?php
namespace PHPoole\Command;

use PHPoole\PHPoole;
use Zend\Console\Adapter\AdapterInterface as Console;
use Zend\Console\ColorInterface as Color;
use ZF\Console\Route;
use PHPoole\Api;

abstract class AbstractCommand
{
    /**
     * @var AdapterInterface
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
     * @var Api
     */
    protected $_api;

    /**
     * Start command processing
     *
     * @param Route   $route
     * @param Console $console
     *
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

        // Instanciate the PHPoole API
        try {
            $this->_api = new Api($this->_path);
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
        $this->_console->write(' DONE ', Color::WHITE, Color::GREEN);
        $this->_console->write(' ');
        $this->_console->writeLine($text, Color::GREEN);
    }
    public function wlAlert($text)
    {
        $this->_console->write(' ALER ', Color::WHITE, Color::YELLOW);
        $this->_console->write(' ');
        $this->_console->writeLine($text, Color::YELLOW);
    }
    public function wlError($text)
    {
        $this->_console->write(' ERRO ', Color::WHITE, Color::RED);
        $this->_console->write(' ');
        $this->_console->writeLine($text, Color::RED);
    }
}
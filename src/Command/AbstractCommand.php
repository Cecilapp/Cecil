<?php
namespace PHPoole\Command;

use Zend\Console\Adapter\AdapterInterface as Console;
use ZF\Console\Route;
use PHPoole;

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
     * @var PHPoole\Api
     */
    protected $_api;

    /**
     * Start command processing
     *
     * @param Route            $route
     * @param AdapterInterface $console
     *
     * @return mixed
     */
    public function __invoke(Route $route, Console $console)
    {
        $this->_route   = $route;
        $this->_console = $console;

        $this->_path = realpath($this->_route->getMatchedParam('path', getcwd()));
        if (!is_dir($this->_path)) {
            $this->_console->write("Invalid directory provided!\n");
            exit(2);
        }
        $this->_path = str_replace(DIRECTORY_SEPARATOR, '/', $this->_path);

        // Instanciate the PHPoole API
        try {
            $this->_api = new PHPoole\Api($this->_path);
        } catch (\Exception $e) {
            $this->_console->write($e->getMessage());
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
}
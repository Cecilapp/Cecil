<?php
namespace PHPoole\Console;

use Zend\Console\Adapter\AdapterInterface as Console;
use ZF\Console\Route;
use PHPoole;

class Init
{
    public function __invoke(Route $route, Console $console)
    {
        $path = $route->getMatchedParam('path', getcwd());
        $force = $route->getMatchedParam('force', false);

        if (!is_dir($path)) {
            $console->write("Invalid directory provided!\n");
            exit(2);
        }
        $path = str_replace(DIRECTORY_SEPARATOR, '/', realpath($path));

        // Instanciate the PHPoole API
        try {
            $phpoole = new PHPoole\Api($path);
        } catch (\Exception $e) {
            $console->write($e->getMessage());
            exit(2);
        }

        $console->write('Initializing new website...' . "\n");

        try {
            $messages = $phpoole->init($force);
            foreach ($messages as $message) {
                $console->write($message . "\n");
            }
        } catch (\Exception $e) {
            $console->write($e->getMessage() . "\n");
        }
    }
}
<?php
namespace PHPoole;

use Zend\Console\Adapter\AdapterInterface as Console;
use ZF\Console\Route;

class Init
{
    public function __invoke(Route $route, Console $console)
    {
        $website = $route->getMatchedParam('website', getcwd());
        
        if (!is_dir($website)) {
            $console->write("Invalid directory provided!\n");
            exit(2);
        }

        $console->write('website=' . $website . "\n");
    }
}
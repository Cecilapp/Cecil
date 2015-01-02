<?php
namespace PHPoole;

use Zend\Console\Adapter\AdapterInterface as Console;
use ZF\Console\Route;

class Init
{
    public function __invoke(Route $route, Console $console)
    {
        $console->write("INIT.\n");
    }
}
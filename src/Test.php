<?php
namespace PHPoole;

use Zend\Console\Adapter\AdapterInterface as Console;
use ZF\Console\Route;

class Test
{
    public function __invoke(Route $route, Console $console)
    {
        $console->write("TEST.\n");
    }
}
<?php
namespace PHPoole\Test\Command;

use PHPoole\Command\NewWebsite;
use PHPUnit_Framework_TestCase;

class NewWebsiteTest extends PHPUnit_Framework_TestCase
{
    protected $route;
    protected $consoleAdapter;
    protected $NewWebsiteCommand;

    public function setUp()
    {
        $this->route = $this->getMockBuilder('ZF\Console\Route')
            ->disableOriginalConstructor()
            ->getMock();
        $this->consoleAdapter = $this->getMock('Zend\Console\Adapter\AdapterInterface');
    }
    public function testNewWebsiteCommandShouldRun()
    {
        $this->NewWebsiteCommand = new NewWebsite(
            $this->route,
            $this->consoleAdapter
        );
    }
}

<?php

namespace PHPoole\Test\App;

use Zend\Console\Console;
use ZF\Console\Application;

class AppTest extends TestCase
{
    /**
     * @var Application
     */
    private $app;

    protected function setUp()
    {
        $config = [
            'name'    => 'PHPoole',
            'version' => '0.0.1',
            'routes'  => [
                [
                    'name'    => 'new',
                    'route'   => '[<path>] [--force|-f]',
                    'aliases' => [
                        'f' => 'force',
                    ],
                    'short_description'    => 'Creates a new website',
                    'description'          => 'Creates a new website in current directory, or in <path> if provided.',
                    'options_descriptions' => [
                        '<path>'     => 'Website path.',
                        '--force|-f' => 'Override if already exist.',
                    ],
                    'defaults' => [
                        'path' => getcwd(),
                    ],
                    'handler' => 'PHPoole\Command\NewWebsite',
                ],
            ],
        ];
        $this->app = new Application(
            $config['name'],
            $config['version'],
            $config['routes'],
            Console::getInstance()
        );
    }

    public function testNameOfAppIntoTheOutput()
    {
        ob_start();
        $this->app->run();
        $content = ob_get_clean();
        $this->assertRegExp('/PHPoole/', $content);
    }
}

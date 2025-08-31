<?php

/**
 * This file is part of Cecil.
 *
 * (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Test;

use Cecil\Builder;
use Cecil\Config;
use Cecil\Logger\PrintLogger;
use Cecil\Util;
use Symfony\Component\Filesystem\Filesystem;

class IntegrationTests extends \PHPUnit\Framework\TestCase
{
    protected $source;
    protected $config;
    protected $destination;
    /**
     * Set to true to keep the generated files after the test.
     * This is useful for debugging purposes, but should not be used in CI.
     */
    public const DEBUG = false;

    public function setUp(): void
    {
        $this->source = Util::joinFile(__DIR__, 'fixtures/website');
        //$this->config = Util::joinFile($this->source, 'config.php');
        $this->config = Util::joinFile($this->source, 'config.yml');
        $this->destination = $this->source;
    }

    public function tearDown(): void
    {
        $fs = new Filesystem();
        if (!self::DEBUG) {
            $fs->remove(Util::joinFile($this->destination, '.cecil'));
            $fs->remove(Util::joinFile($this->destination, '.cache'));
            $fs->remove(Util::joinFile($this->destination, '_site'));
        }
    }

    public function testBuild()
    {
        putenv('CECIL_DEBUG=true');
        putenv('CECIL_TITLE=Cecil (env)');
        putenv('CECIL_DESCRIPTION=Description (env)');
        echo "\n";
        //Builder::create(require($this->config), new PrintLogger())
        Builder::create(Config::loadFile($this->config), new PrintLogger())
            ->setSourceDir($this->source)
            ->setDestinationDir($this->destination)
            ->build([
                'drafts'  => true,
                'dry-run' => false,
            ]);
        self::assertTrue(true);
    }
}

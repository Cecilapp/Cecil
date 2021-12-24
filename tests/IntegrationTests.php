<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Test;

use Cecil\Builder;
use Cecil\Logger\PrintLogger;
use Cecil\Util;
use Symfony\Component\Filesystem\Filesystem;

class IntegrationTests extends \PHPUnit\Framework\TestCase
{
    protected $wsSourceDir;
    protected $config;
    protected $wsDestinationDir;
    const DEBUG = false;

    public function setUp(): void
    {
        $this->wsSourceDir = Util::joinFile(__DIR__, 'fixtures/website');
        $this->config = Util::joinFile($this->wsSourceDir, 'config.php');
        $this->wsDestinationDir = $this->wsSourceDir;
    }

    public function tearDown(): void
    {
        $fs = new Filesystem();
        if (!self::DEBUG) {
            $fs->remove(Util::joinFile($this->wsDestinationDir, '_site'));
            $fs->remove(Util::joinFile($this->wsDestinationDir, '.cache'));
        }
    }

    public function testBuid()
    {
        putenv('CECIL_DEBUG=true');
        putenv('CECIL_TITLE=Cecil');
        putenv('CECIL_DESCRIPTION=Description (env. variable)');
        echo "\n";
        Builder::create(
            require($this->config),
            new PrintLogger(Builder::VERBOSITY_DEBUG)
        )->setSourceDir($this->wsSourceDir)
        ->setDestinationDir($this->wsDestinationDir)
        ->build([
            'drafts'  => true,
            'dry-run' => false,
        ]);

        self::assertTrue(true);
    }
}

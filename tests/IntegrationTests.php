<?php

/*
 * This file is part of Cecil.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
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
    protected $source;
    protected $config;
    protected $destination;
    public const DEBUG = false;

    public function setUp(): void
    {
        $this->source = Util::joinFile(__DIR__, 'fixtures/website');
        $this->config = Util::joinFile($this->source, 'config.php');
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

    public function testBuid()
    {
        putenv('CECIL_DEBUG=true');
        putenv('CECIL_TITLE=Cecil (env)');
        putenv('CECIL_DESCRIPTION=Description (env)');
        echo "\n";
        Builder::create(require($this->config), new PrintLogger())
            ->setSourceDir($this->source)
            ->setDestinationDir($this->destination)
            ->build([
                'drafts'  => true,
                'dry-run' => false,
            ]);

        self::assertTrue(true);
    }
}

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

use Cecil\Util;
use Symfony\Component\Filesystem\Filesystem;

class IntegrationCliTests extends IntegrationTests
{
    /**
     * Set to true to keep the generated files after the test.
     * This is useful for debugging purposes, but should not be used in CI.
     */
    public const DEBUG = false;

    public function tearDown(): void
    {
        $fs = new Filesystem();
        if (!self::DEBUG) {
            $fs->remove(Util::joinFile(__DIR__, 'demo'));
        }
    }

    public function testBuild()
    {
        echo "\n";
        exec('php ./bin/cecil new:site tests/demo --demo -n -f', $output, $retval);
        self::assertTrue($retval < 1);
        exec('php ./bin/cecil build tests/demo -v', $output, $retval);
        echo implode("\n", $output);
        self::assertTrue($retval < 1);
    }
}

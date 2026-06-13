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

use Cecil\Util;
use Symfony\Component\Filesystem\Filesystem;

class IntegrationCliTests extends IntegrationTests
{
    /**
     * Set to true to keep the generated files after the test.
     * This is useful for debugging purposes, but should not be used in CI.
     */
    public const DEBUG = false;

    /** @var int|null PID of any background server process started during a test */
    private ?int $backgroundPid = null;

    public function tearDown(): void
    {
        // ensure any background server process is killed even if the test fails
        if ($this->backgroundPid !== null) {
            $pidFile = Util::joinFile(__DIR__, 'demo', '.cecil', 'server.pid');
            if (is_file($pidFile) && (int) file_get_contents($pidFile) === $this->backgroundPid) {
                if (DIRECTORY_SEPARATOR === '\\') {
                    exec(\sprintf('taskkill /F /PID %d 2>NUL', $this->backgroundPid));
                } else {
                    exec(\sprintf('kill %d 2>/dev/null', $this->backgroundPid));
                }
            }
            $this->backgroundPid = null;
        }
        $fs = new Filesystem();
        if (!self::DEBUG) {
            $fs->remove(Util::joinFile(__DIR__, 'demo'));
        }
    }

    public function testAbout(): void
    {
        exec('php ./bin/cecil about', $output, $retval);
        echo implode("\n", $output);
        self::assertTrue($retval < 1);
    }

    public function testBuild(): void
    {
        exec('php ./bin/cecil new:site tests/demo --demo -n -f', $output, $retval);
        self::assertTrue($retval < 1);
        $output = [];
        exec('php ./bin/cecil build tests/demo', $output, $retval);
        //echo implode("\n", $output);
        self::assertTrue($retval < 1);
    }

    public function testBuildVerbose(): void
    {
        exec('php ./bin/cecil new:site tests/demo --demo -n -f', $output, $retval);
        self::assertTrue($retval < 1);
        $output = [];
        exec('php ./bin/cecil build tests/demo -v', $output, $retval);
        echo implode("\n", $output);
        self::assertTrue($retval < 1);
    }

    public function testDoctor(): void
    {
        exec('php ./bin/cecil new:site tests/demo --demo -n -f', $output, $retval);
        self::assertTrue($retval < 1);
        $output = [];
        exec('php ./bin/cecil doctor tests/demo', $output, $retval);
        $output = implode("\n", $output);
        echo $output;
        self::assertTrue($retval < 1);
        self::assertStringContainsString('Environment', $output);
    }

    public function testDoctorSeo(): void
    {
        exec('php ./bin/cecil new:site tests/demo --demo -n -f 2>&1', $output, $retval);
        self::assertTrue($retval < 1);
        $output = [];
        exec('php ./bin/cecil doctor:seo tests/demo 2>&1', $output, $retval);
        $output = implode("\n", $output);
        echo $output;
        self::assertTrue($retval < 1);
        self::assertStringContainsString('SEO audit summary', $output);
    }

    public function testDoctorSeoJson(): void
    {
        exec('php ./bin/cecil new:site tests/demo --demo -n -f 2>&1', $output, $retval);
        self::assertTrue($retval < 1);
        $output = [];
        exec('php ./bin/cecil doctor:seo tests/demo --format=json', $output, $retval);
        $output = implode("\n", $output);
        echo $output;
        self::assertTrue($retval < 1);
        $json = json_decode($output, true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($json);
        self::assertArrayHasKey('summary', $json);
        self::assertArrayHasKey('findings', $json);
    }

    public function testDoctorSeoIncludeVirtual(): void
    {
        exec('php ./bin/cecil new:site tests/demo --demo -n -f 2>&1', $output, $retval);
        self::assertTrue($retval < 1);
        $output = [];
        exec('php ./bin/cecil doctor:seo tests/demo --include-virtual 2>&1', $output, $retval);
        $output = implode("\n", $output);
        echo $output;
        self::assertTrue($retval < 1);
        self::assertStringContainsString('SEO audit summary', $output);
    }

    public function testServeBackgroundWithPidFile(): void
    {
        $this->markTestSkipped('Skipping serve background test because it is not reliable in CI environments');
        exec('php ./bin/cecil new:site tests/demo --demo -n -f', $output, $retval);
        self::assertTrue($retval < 1);
        $output = [];
        exec('php ./bin/cecil serve tests/demo --background --no-watch 2>&1', $output, $retval);
        self::assertSame(0, $retval, 'serve --background should exit with code 0');
        $pidFile = Util::joinFile(__DIR__, 'demo', '.cecil', 'server.pid');
        self::assertFileExists($pidFile, 'PID file should exist before stop');
        $this->backgroundPid = (int) file_get_contents($pidFile);
        $output = [];
        exec('php ./bin/cecil serve:stop tests/demo 2>&1', $output, $retval);
        echo implode("\n", $output);
        self::assertSame(0, $retval, 'serve:stop should exit with code 0');
        self::assertFileDoesNotExist($pidFile, 'PID file should be removed by serve:stop');
        $this->backgroundPid = null;
    }
}

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
        self::assertSame(0, $json['summary']['feedback_count']);

        foreach ($json['findings'] as $finding) {
            self::assertNotSame('feedback', $finding['level']);
        }
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

    public function testDoctorSeoIncludeFeedbackJson(): void
    {
        exec('php ./bin/cecil new:site tests/demo --demo -n -f 2>&1', $output, $retval);
        self::assertTrue($retval < 1);

        $output = [];
        exec('php ./bin/cecil doctor:seo tests/demo --format=json', $output, $retval);
        self::assertTrue($retval < 1);
        $allFindings = json_decode(implode("\n", $output), true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($allFindings);

        $output = [];
        exec('php ./bin/cecil doctor:seo tests/demo --format=json --feedback', $output, $retval);
        self::assertTrue($retval < 1);
        $withFeedback = json_decode(implode("\n", $output), true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($withFeedback);

        self::assertSame($allFindings['summary']['pages_audited'], $withFeedback['summary']['pages_audited']);
        self::assertGreaterThanOrEqual($allFindings['summary']['bad_count'], $withFeedback['summary']['bad_count']);
        self::assertGreaterThanOrEqual($allFindings['summary']['ok_count'], $withFeedback['summary']['ok_count']);
        self::assertGreaterThanOrEqual($allFindings['summary']['feedback_count'], $withFeedback['summary']['feedback_count']);
        self::assertGreaterThanOrEqual(\count($allFindings['findings']), \count($withFeedback['findings']));

        $containsFeedback = false;
        foreach ($withFeedback['findings'] as $finding) {
            if ($finding['level'] === 'feedback') {
                $containsFeedback = true;
                break;
            }
        }
        if ($withFeedback['summary']['feedback_count'] > 0) {
            self::assertTrue($containsFeedback);
        }
    }

    public function testDoctorSeoTruncatesLongPageLabelInTable(): void
    {
        exec('php ./bin/cecil new:site tests/demo --demo -n -f 2>&1', $output, $retval);
        self::assertTrue($retval < 1);

        $longFilename = 'this-is-a-very-very-long-page-filename-for-seo-table-output.md';
        $longPagePath = Util::joinFile(__DIR__, 'demo', 'pages', $longFilename);
        file_put_contents($longPagePath, "---\ntitle: Long page\n---\n\nShort content.\n");

        $output = [];
        exec('php ./bin/cecil doctor:seo tests/demo 2>&1', $output, $retval);
        $output = implode("\n", $output);
        echo $output;

        self::assertTrue($retval < 1);

        $pageLabel = '/this-is-a-very-very-long-page-filename-for-seo-table-output/';
        $expected = mb_substr($pageLabel, 0, 28) . '...' . mb_substr($pageLabel, -29);
        self::assertStringContainsString($expected, $output);
        self::assertStringNotContainsString($pageLabel, $output);
    }

    public function testDoctorFrontmatter(): void
    {
        exec('php ./bin/cecil new:site tests/demo --demo -n -f 2>&1', $output, $retval);
        self::assertTrue($retval < 1);

        $invalidPage = Util::joinFile(__DIR__, 'demo', 'pages', 'invalid-frontmatter.md');
        file_put_contents($invalidPage, "---\ntitle: \"Unclosed\n---\n\nBody\n");

        $output = [];
        exec('php ./bin/cecil doctor:frontmatter tests/demo 2>&1', $output, $retval);
        $output = implode("\n", $output);
        echo $output;

        self::assertTrue($retval < 1);
        self::assertStringContainsString('Front matter audit summary', $output);
        self::assertStringContainsString('error(s) found', $output);
        self::assertStringContainsString('invalid-frontmatter.md', $output);
        self::assertStringContainsString('File', $output);
        self::assertStringContainsString('Status', $output);
        self::assertStringContainsString('Line', $output);
        self::assertStringContainsString('FAIL', $output);
        self::assertStringContainsString('Malformed inline YAML string', $output);
        self::assertStringNotContainsString('tests\\demo\\pages\\invalid-frontmatter.md', $output);
    }

    public function testDoctorFrontmatterAlias(): void
    {
        exec('php ./bin/cecil new:site tests/demo --demo -n -f 2>&1', $output, $retval);
        self::assertTrue($retval < 1);

        $invalidPage = Util::joinFile(__DIR__, 'demo', 'pages', 'invalid-frontmatter-alias.md');
        file_put_contents($invalidPage, "---\ntitle: \"Unclosed\n---\n\nBody\n");

        $output = [];
        exec('php ./bin/cecil doctor:fm tests/demo 2>&1', $output, $retval);
        $output = implode("\n", $output);
        echo $output;

        self::assertTrue($retval < 1);
        self::assertStringContainsString('Front matter audit summary', $output);
        self::assertStringContainsString('invalid-frontmatter-alias.md', $output);
        self::assertStringContainsString('error(s) found', $output);
        self::assertStringContainsString('Status', $output);
        self::assertStringContainsString('Line', $output);
    }

    public function testDoctorFrontmatterMultipleErrorsInSingleFile(): void
    {
        exec('php ./bin/cecil new:site tests/demo --demo -n -f 2>&1', $output, $retval);
        self::assertTrue($retval < 1);

        $invalidPage = Util::joinFile(__DIR__, 'demo', 'pages', 'invalid-frontmatter-multiple.md');
        file_put_contents($invalidPage, "---\ntitle: \"Unclosed\nitems: [a, b\nbad: key: value\n---\n\nBody\n");

        $output = [];
        exec('php ./bin/cecil doctor:frontmatter tests/demo 2>&1', $output, $retval);
        $output = implode("\n", $output);
        echo $output;

        self::assertTrue($retval < 1);
        self::assertStringContainsString('invalid-frontmatter-multiple.md', $output);
        self::assertGreaterThanOrEqual(3, substr_count($output, 'invalid-frontmatter-multiple.md'));
        self::assertStringContainsString('3 error(s) found', $output);
        self::assertStringContainsString('line 3 (near "bad: key: value")', $output);
        self::assertStringContainsString('line 2 (near "items: [a, b")', $output);
        self::assertStringContainsString('line 1 (near "title: "Unclosed")', $output);
    }

    public function testDoctorFrontmatterNoErrors(): void
    {
        exec('php ./bin/cecil new:site tests/demo --demo -n -f 2>&1', $output, $retval);
        self::assertTrue($retval < 1);

        $output = [];
        exec('php ./bin/cecil doctor:frontmatter tests/demo 2>&1', $output, $retval);
        $output = implode("\n", $output);
        echo $output;

        self::assertTrue($retval < 1);
        self::assertStringContainsString('Front matter audit summary', $output);
        self::assertStringContainsString('No front matter errors found.', $output);
        self::assertStringNotContainsString('FAIL', $output);
        self::assertStringContainsString('Errors in front matter', $output);
        self::assertStringContainsString('│ 0', $output);
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

    public function testServeLog(): void
    {
        $fs = new Filesystem();
        exec('php ./bin/cecil new:site tests/demo --demo -n -f', $output, $retval);
        self::assertTrue($retval < 1);
        $output = [];
        exec('php ./bin/cecil build tests/demo', $output, $retval);
        self::assertTrue($retval < 1);

        // Create log files for testing
        $logDir = Util::joinFile(__DIR__, 'demo', '.cecil');
        $fs->mkdir($logDir, 0755);
        file_put_contents(Util::joinFile($logDir, 'errors.log'), "[Thu Jun 25 15:48:15 2026] PHP 8.2.30 Development Server (http://localhost:8000) started\n");
        file_put_contents(Util::joinFile($logDir, 'server.log'), "[Thu Jun 25 15:48:16 2026] ::1:56597 [200]: /\n[Thu Jun 25 15:48:17 2026] ::1:56598 [404]: /notfound\n");

        $output = [];
        exec('php ./bin/cecil serve:log tests/demo', $output, $retval);
        $output = implode("\n", $output);
        echo $output;
        self::assertSame(0, $retval, 'serve:log should exit with code 0');
        self::assertStringContainsString('Development Server', $output);
        self::assertStringContainsString('[200]', $output);
    }
}

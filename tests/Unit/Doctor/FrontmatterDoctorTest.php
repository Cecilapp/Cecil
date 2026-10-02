<?php

/**
 * This file is part of Cecil.
 *
 * (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cecil\Test\Unit\Doctor;

use Cecil\Builder;
use Cecil\Doctor\FrontmatterDoctor;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Filesystem\Filesystem;

class FrontmatterDoctorTest extends TestCase
{
    private string $tmpDir;

    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cecil-frontmatter-doctor-test-' . uniqid('', true);
        $this->filesystem->mkdir($this->tmpDir);
    }

    protected function tearDown(): void
    {
        $this->filesystem->chmod($this->tmpDir, 0o755, 0o000, true);
        $this->filesystem->remove($this->tmpDir);
    }

    public function testMissingPagesDirectoryReturnsEmptyReport(): void
    {
        $report = (new FrontmatterDoctor())->diagnose($this->builder());

        self::assertSame([
            'files_scanned' => 0,
            'files_with_frontmatter' => 0,
            'valid_frontmatters' => 0,
            'invalid_frontmatters' => 0,
            'files_without_frontmatter' => 0,
        ], $report['summary']);
        self::assertSame([], $report['findings']);
    }

    public function testValidMissingAndInvalidYamlFrontmatters(): void
    {
        $this->page('valid.md', "---\ntitle: Valid\ntags: [a, b]\n---\nBody");
        $this->page('no-frontmatter.md', 'Just a body');
        $this->page('invalid.md', "---\ntitle: Invalid\nbad: [unclosed\n---\nBody");

        $report = (new FrontmatterDoctor())->diagnose($this->builder());

        self::assertSame(3, $report['summary']['files_scanned']);
        self::assertSame(2, $report['summary']['files_with_frontmatter']);
        self::assertSame(1, $report['summary']['valid_frontmatters']);
        self::assertSame(1, $report['summary']['files_without_frontmatter']);
        self::assertGreaterThanOrEqual(1, $report['summary']['invalid_frontmatters']);
        self::assertCount($report['summary']['invalid_frontmatters'], $report['findings']);

        $finding = $report['findings'][0];
        self::assertSame('invalid.md', $finding['file']);
        self::assertStringEndsWith('invalid.md', $finding['file_absolute']);
        self::assertSame('error', $finding['status']);
        self::assertNotSame('', $finding['details']);
    }

    public function testYamlParsingRetriesToReportSeveralErrors(): void
    {
        $this->page('errors.md', "---\ntitle: ok\none: \"unclosed\ntwo: ok\n  three: indented\nfour: [unclosed\n---\nBody");

        $report = (new FrontmatterDoctor())->diagnose($this->builder());

        self::assertGreaterThan(1, $report['summary']['invalid_frontmatters']);
        $lines = array_column($report['findings'], 'line');
        // reported lines are mapped back to the original front matter lines
        foreach ($lines as $line) {
            self::assertTrue($line === null || ($line >= 1 && $line <= 5));
        }
        // the same error is never reported twice
        $fingerprints = array_map(static fn (array $f): string => $f['details'] . '|' . $f['line'], $report['findings']);
        self::assertSame($fingerprints, array_values(array_unique($fingerprints)));
    }

    public function testYamlScalarFrontmatterIsReportedWithoutLine(): void
    {
        $this->page('scalar.md', "---\njust a string\n---\nBody");

        $report = (new FrontmatterDoctor())->diagnose($this->builder());

        self::assertSame(1, $report['summary']['invalid_frontmatters']);
        self::assertNull($report['findings'][0]['line']);
        self::assertSame('Unable to parse YAML front matter.', $report['findings'][0]['details']);
    }

    public function testNonYamlFormatIsValidatedOnce(): void
    {
        $this->page('valid.md', "---\n{\"title\": \"Valid\"}\n---\nBody");
        $this->page('invalid.md', "---\n{\"title\": \n---\nBody");

        $report = (new FrontmatterDoctor())->diagnose($this->builder(['pages' => ['frontmatter' => 'json']]));

        self::assertSame(2, $report['summary']['files_with_frontmatter']);
        self::assertSame(1, $report['summary']['valid_frontmatters']);
        self::assertSame(1, $report['summary']['invalid_frontmatters']);
        self::assertSame('invalid.md', $report['findings'][0]['file']);
        self::assertNull($report['findings'][0]['line']);
        self::assertSame('Unable to parse JSON front matter.', $report['findings'][0]['details']);
    }

    public function testPageOptionRestrictsDiagnosisToOneFile(): void
    {
        $this->page('one.md', "---\ntitle: One\n---\nBody");
        $this->page('two.md', "---\ntitle: Two\n---\nBody");

        $report = (new FrontmatterDoctor())->diagnose($this->builder(), ['page' => 'one.md']);

        self::assertSame(1, $report['summary']['files_scanned']);
        self::assertSame(1, $report['summary']['valid_frontmatters']);
    }

    public function testUnreadableFileIsReportedAsError(): void
    {
        $file = $this->page('unreadable.md', "---\ntitle: Unreadable\n---\nBody");
        $this->filesystem->chmod($file, 0o000);
        if (is_readable($file)) {
            self::markTestSkipped('File permissions cannot be restricted on this platform.');
        }

        $report = (new FrontmatterDoctor())->diagnose($this->builder());

        self::assertSame(1, $report['summary']['invalid_frontmatters']);
        self::assertSame('Cannot read file.', $report['findings'][0]['details']);
    }

    private function builder(array $config = []): Builder
    {
        $builder = new Builder($config, new NullLogger());
        $builder->getConfig()->setSourceDir($this->tmpDir)->setDestinationDir($this->tmpDir);

        return $builder;
    }

    private function page(string $name, string $content): string
    {
        $path = $this->tmpDir . DIRECTORY_SEPARATOR . 'pages' . DIRECTORY_SEPARATOR . $name;
        $this->filesystem->dumpFile($path, $content);

        return $path;
    }
}

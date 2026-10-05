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
use Cecil\Config;
use Cecil\Doctor\SiteDoctor;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Filesystem\Filesystem;

class SiteDoctorTest extends TestCase
{
    private string $tmpDir;

    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cecil-site-doctor-test-' . uniqid('', true);
        $this->filesystem->mkdir($this->tmpDir);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->tmpDir);
    }

    public function testHealthySiteHasNoWarningsNorErrors(): void
    {
        $this->filesystem->mkdir(array_map(fn (string $dir): string => $this->tmpDir . DIRECTORY_SEPARATOR . $dir, [
            'pages', 'data', 'assets', 'static', 'layouts',
        ]));

        $report = (new SiteDoctor())->diagnose(
            $this->builder(['baseurl' => 'https://example.com', 'canonicalurl' => true]),
            $this->tmpDir,
            ['cecil.yml', 'cecil.local.yml']
        );

        self::assertSame(0, $report['warnings'], print_r($report['checks'], true));
        self::assertSame(0, $report['errors'], print_r($report['checks'], true));
        self::assertSame('https://example.com/', $this->check($report, 'Base URL')['details']);
        self::assertSame('Enabled (base URL is valid)', $this->check($report, 'Canonical URL mode')['details']);
        self::assertSame('Creatable (parent writable)', $this->check($report, 'Output directory')['details']);
        self::assertSame('Creatable (parent writable)', $this->check($report, 'Cache directory')['details']);
        self::assertSame('None configured', $this->check($report, 'Theme(s)')['details']);
        self::assertSame('1 language(s), default: en', $this->check($report, 'Languages configuration')['details']);
        self::assertStringContainsString('mapping is coherent', $this->check($report, 'Output formats mapping')['details']);
        self::assertSame('ok', $this->check($report, 'PHP version requirement')['status']);

        $environment = array_column($report['environment'], 1, 0);
        self::assertSame(Builder::getVersion(), $environment['Cecil version']);
        self::assertSame(PHP_VERSION, $environment['PHP version']);
        self::assertSame($this->tmpDir, $environment['Working directory']);

        $paths = array_column($report['paths'], 1, 0);
        self::assertSame("cecil.yml,\ncecil.local.yml", $paths['Config files']);
        self::assertStringStartsWith($this->tmpDir, $paths['Cache']);
    }

    public function testMissingBaseUrlAndDirectoriesAreReported(): void
    {
        $report = (new SiteDoctor())->diagnose($this->builder(['canonicalurl' => true]), $this->tmpDir, []);

        self::assertSame('warning', $this->check($report, 'Base URL')['status']);
        self::assertSame('Not set', $this->check($report, 'Base URL')['details']);
        self::assertSame('error', $this->check($report, 'Canonical URL mode')['status']);
        self::assertSame('warning', $this->check($report, 'Pages directory')['status']);
        self::assertSame('warning', $this->check($report, 'Data directory')['status']);
        self::assertSame('warning', $this->check($report, 'Assets directory')['status']);
        self::assertSame('warning', $this->check($report, 'Static directory')['status']);
        self::assertSame('error', $this->check($report, 'Layouts directory')['status']);
        self::assertSame('None', array_column($report['paths'], 1, 0)['Config files']);
        self::assertGreaterThanOrEqual(5, $report['warnings']);
        self::assertGreaterThanOrEqual(2, $report['errors']);
    }

    public function testInvalidBaseUrlIsAnError(): void
    {
        $report = (new SiteDoctor())->diagnose($this->builder(['baseurl' => 'not a valid url']), $this->tmpDir, []);

        $check = $this->check($report, 'Base URL');
        self::assertSame('error', $check['status']);
        self::assertNotSame('Configured', $check['details']);
        self::assertSame('Disabled', $this->check($report, 'Canonical URL mode')['details']);
    }

    public function testRootBaseUrlIsAccepted(): void
    {
        $report = (new SiteDoctor())->diagnose($this->builder(['baseurl' => '/']), $this->tmpDir, []);

        self::assertSame(['item' => 'Base URL', 'status' => 'ok', 'details' => '/'], $this->check($report, 'Base URL'));
    }

    public function testMissingThemeIsAnError(): void
    {
        $report = (new SiteDoctor())->diagnose($this->builder(['theme' => 'unknown']), $this->tmpDir, []);

        $check = $this->check($report, 'Theme(s)');
        self::assertSame('error', $check['status']);
        self::assertStringContainsString('Theme "unknown" not found', $check['details']);
        self::assertSame('error', $this->check($report, 'Layouts directory')['status']);
    }

    public function testInstalledThemeProvidesLayouts(): void
    {
        $this->filesystem->mkdir($this->tmpDir . '/themes/foo/layouts');
        $this->filesystem->mkdir($this->tmpDir . '/themes/bar/layouts');

        $report = (new SiteDoctor())->diagnose($this->builder(['theme' => ['foo', 'bar']]), $this->tmpDir, []);

        self::assertSame(['item' => 'Theme(s)', 'status' => 'ok', 'details' => 'foo, bar'], $this->check($report, 'Theme(s)'));
        self::assertSame('ok', $this->check($report, 'Layouts directory')['status']);
    }

    public function testDisabledCacheIsAWarning(): void
    {
        $report = (new SiteDoctor())->diagnose($this->builder(['cache' => ['enabled' => false]]), $this->tmpDir, []);

        self::assertSame('ok', $this->check($report, 'Cache directory')['status']);
        self::assertSame('Not required (cache is disabled)', $this->check($report, 'Cache directory')['details']);
        self::assertSame('warning', $this->check($report, 'Cache')['status']);
    }

    public function testUndefinedCacheDirectoryIsAnError(): void
    {
        $report = (new SiteDoctor())->diagnose($this->builderWithUnvalidatedValues(['cache.dir' => '']), $this->tmpDir, []);

        self::assertSame('error', $this->check($report, 'Cache directory')['status']);
        self::assertStringContainsString('`cache.dir`', $this->check($report, 'Cache directory')['details']);
        self::assertSame('Undefined', array_column($report['paths'], 1, 0)['Cache']);
    }

    public function testAbsoluteCacheDirectoryIsChecked(): void
    {
        $cacheDir = $this->tmpDir . DIRECTORY_SEPARATOR . 'abs-cache';
        $this->filesystem->mkdir($cacheDir . DIRECTORY_SEPARATOR . 'cecil');

        $report = (new SiteDoctor())->diagnose($this->builder(['cache' => ['dir' => $cacheDir]]), $this->tmpDir, []);

        self::assertSame(['item' => 'Cache directory', 'status' => 'ok', 'details' => 'Writable'], $this->check($report, 'Cache directory'));
        self::assertStringStartsWith($cacheDir, array_column($report['paths'], 1, 0)['Cache']);
    }

    public function testOutputPathThatIsAFileIsAnError(): void
    {
        $this->filesystem->dumpFile($this->tmpDir . DIRECTORY_SEPARATOR . '_site', 'not a directory');

        $report = (new SiteDoctor())->diagnose($this->builder(), $this->tmpDir, []);

        self::assertSame(
            ['item' => 'Output directory', 'status' => 'error', 'details' => 'Path exists and is not a directory'],
            $this->check($report, 'Output directory')
        );
    }

    public function testExistingOutputDirectoryIsWritable(): void
    {
        $this->filesystem->mkdir($this->tmpDir . DIRECTORY_SEPARATOR . '_site');

        $report = (new SiteDoctor())->diagnose($this->builder(), $this->tmpDir, []);

        self::assertSame('Writable', $this->check($report, 'Output directory')['details']);
    }

    public function testUnknownOutputFormatReferenceIsAnError(): void
    {
        $builder = $this->builder(['output' => ['pagetypeformats' => ['page' => ['html', 'unknown']]]]);

        $report = (new SiteDoctor())->diagnose($builder, $this->tmpDir, []);

        $check = $this->check($report, 'Output formats mapping');
        self::assertSame('error', $check['status']);
        self::assertSame('Unknown format reference(s): page:unknown', $check['details']);
    }

    public function testMissingOutputFormatsIsAnError(): void
    {
        $builder = $this->builderWithUnvalidatedValues(['output.formats' => [['name' => ''], 'invalid']]);

        $report = (new SiteDoctor())->diagnose($builder, $this->tmpDir, []);

        self::assertSame(
            ['item' => 'Output formats mapping', 'status' => 'error', 'details' => 'No output format defined'],
            $this->check($report, 'Output formats mapping')
        );
    }

    public function testDuplicateLanguageCodesAreAnError(): void
    {
        $builder = $this->builder(['languages' => [
            ['code' => 'en', 'name' => 'English', 'locale' => 'en_US'],
            ['code' => 'en', 'name' => 'English (bis)', 'locale' => 'en_GB'],
        ]]);

        $report = (new SiteDoctor())->diagnose($builder, $this->tmpDir, []);

        self::assertSame(
            ['item' => 'Languages configuration', 'status' => 'error', 'details' => 'Duplicate language codes found'],
            $this->check($report, 'Languages configuration')
        );
    }

    public function testDefaultLanguageNotListedIsAnError(): void
    {
        $builder = $this->builder(['language' => 'fr']);

        $report = (new SiteDoctor())->diagnose($builder, $this->tmpDir, []);

        $check = $this->check($report, 'Languages configuration');
        self::assertSame('error', $check['status']);
        self::assertStringContainsString('"fr" is not listed', $check['details']);
    }

    private function builder(array $config = []): Builder
    {
        $builder = new Builder($config, new NullLogger());
        $builder->getConfig()->setSourceDir($this->tmpDir)->setDestinationDir($this->tmpDir);

        return $builder;
    }

    /**
     * Returns a builder whose configuration returns the given values without schema validation.
     *
     * @param array<string, mixed> $values
     */
    private function builderWithUnvalidatedValues(array $values): Builder
    {
        $config = new class () extends Config {
            /** @var array<string, mixed> */
            public array $values = [];

            public function get(string $key, ?string $language = null, bool $fallback = true)
            {
                return \array_key_exists($key, $this->values) ? $this->values[$key] : parent::get($key, $language, $fallback);
            }
        };
        $config->values = $values;
        $config->setSourceDir($this->tmpDir)->setDestinationDir($this->tmpDir);

        return new Builder($config, new NullLogger());
    }

    /**
     * @return array{item: string, status: string, details: string}
     */
    private function check(array $report, string $item): array
    {
        foreach ($report['checks'] as $check) {
            if ($check['item'] === $item) {
                return $check;
            }
        }

        self::fail(\sprintf('Check "%s" not found.', $item));
    }
}

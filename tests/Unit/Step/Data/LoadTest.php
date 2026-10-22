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

namespace Cecil\Test\Unit\Step\Data;

use Cecil\Builder;
use Cecil\Logger\PrintLogger;
use Cecil\Step\Data\Load;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class LoadTest extends TestCase
{
    private string $root;

    private string $sourceDir;

    private string $destinationDir;

    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cecil-data-load-test-' . uniqid('', true);
        $this->sourceDir = $this->root . DIRECTORY_SEPARATOR . 'source';
        $this->destinationDir = $this->root . DIRECTORY_SEPARATOR . 'destination';
        $this->filesystem->mkdir([$this->sourceDir, $this->destinationDir]);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->root);
    }

    public function testThemeDataIsLoadedWithoutSiteDataDirectory(): void
    {
        $themeDir = $this->sourceDir . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . 'a';
        $this->filesystem->dumpFile($themeDir . DIRECTORY_SEPARATOR . 'config.yml', "title: Theme\n");
        $this->filesystem->dumpFile($themeDir . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'icons.yml', "star: '*'\n");

        $builder = $this->createBuilder(['theme' => ['a']]);

        $step = new Load($builder);
        $step->init([]);

        self::assertTrue($step->canProcess());

        $step->process();

        self::assertSame(['star' => '*'], $builder->getData()['en']['icons'] ?? null);
    }

    public function testStepIsSkippedWithoutAnyDataDirectory(): void
    {
        $builder = $this->createBuilder([]);

        $step = new Load($builder);
        $step->init([]);

        self::assertFalse($step->canProcess());
    }

    private function createBuilder(array $config): Builder
    {
        $builder = new Builder(array_merge(['baseurl' => 'https://example.com/'], $config), new PrintLogger(Builder::VERBOSITY_QUIET));
        $builder->setSourceDir($this->sourceDir);
        $builder->setDestinationDir($this->destinationDir);

        return $builder;
    }
}

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

namespace Cecil\Test\Unit\Step\Assets;

use Cecil\Builder;
use Cecil\Cache;
use Cecil\Logger\PrintLogger;
use Cecil\Step\Assets\Save;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class SaveTest extends TestCase
{
    private string $root;

    private string $sourceDir;

    private string $destinationDir;

    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cecil-save-test-' . uniqid('', true);
        $this->sourceDir = $this->root . DIRECTORY_SEPARATOR . 'source';
        $this->destinationDir = $this->root . DIRECTORY_SEPARATOR . 'destination';
        $this->filesystem->mkdir([$this->sourceDir, $this->destinationDir]);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->root);
    }

    public function testMissingAssetInCacheIsSkippedAndNextAssetsAreSaved(): void
    {
        $builder = new Builder([
            'baseurl' => 'https://example.com/',
        ], new PrintLogger(Builder::VERBOSITY_QUIET));
        $builder->setSourceDir($this->sourceDir);
        $builder->setDestinationDir($this->destinationDir);
        $cache = new Cache($builder, 'assets');

        $this->filesystem->dumpFile($cache->getContentFile('/first.css'), 'a{}');
        $this->filesystem->dumpFile($cache->getContentFile('/third.css'), 'c{}');
        $builder->addToAssetsList('/first.css');
        $builder->addToAssetsList('/missing.css');
        $builder->addToAssetsList('/third.css');

        $step = new Save($builder);
        $step->init([]);
        $step->process();

        $outputPath = $builder->getConfig()->getOutputPath();
        self::assertFileExists($outputPath . DIRECTORY_SEPARATOR . 'first.css');
        self::assertFileDoesNotExist($outputPath . DIRECTORY_SEPARATOR . 'missing.css');
        self::assertFileExists($outputPath . DIRECTORY_SEPARATOR . 'third.css');
    }
}

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

namespace Cecil\Test\Unit;

use Cecil\Builder;
use Cecil\Cache;
use Cecil\Logger\PrintLogger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class CacheTest extends TestCase
{
    private string $root;

    private string $sourceDir;

    private string $destinationDir;

    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cecil-cache-test-' . uniqid('', true);
        $this->sourceDir = $this->root . DIRECTORY_SEPARATOR . 'source';
        $this->destinationDir = $this->root . DIRECTORY_SEPARATOR . 'destination';

        $this->filesystem->mkdir([
            $this->sourceDir,
            $this->destinationDir,
        ]);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->root);
    }

    public function testDeleteRemovesAssociatedContentFile(): void
    {
        $cache = $this->createCache();
        $key = $cache->createKey('stylesheet', name: 'foo');
        $contentPath = 'styles/site.css';

        self::assertTrue($cache->set($key, [
            'content' => 'body { color: red; }',
            'path'    => $contentPath,
        ]));
        self::assertFileExists($cache->getContentFile($contentPath));

        self::assertTrue($cache->delete($key));

        self::assertFalse($cache->has($key));
        self::assertFileDoesNotExist($cache->getContentFile($contentPath));
    }

    public function testClearByPatternRemovesAssociatedContentFile(): void
    {
        $cache = $this->createCache();
        $removedKey = $cache->createKey('stylesheet', name: 'foo');
        $keptKey = $cache->createKey('stylesheet', name: 'bar');
        $removedContentPath = 'styles/removed.css';
        $keptContentPath = 'styles/kept.css';

        $cache->set($removedKey, [
            'content' => 'body { color: red; }',
            'path'    => $removedContentPath,
        ]);
        $cache->set($keptKey, [
            'content' => 'body { color: blue; }',
            'path'    => $keptContentPath,
        ]);

        self::assertSame(1, $cache->clearByPattern('foo'));

        self::assertFalse($cache->has($removedKey));
        self::assertFileDoesNotExist($cache->getContentFile($removedContentPath));
        self::assertTrue($cache->has($keptKey));
        self::assertFileExists($cache->getContentFile($keptContentPath));
    }

    private function createCache(): Cache
    {
        $builder = new Builder([
            'baseurl' => 'https://example.com/',
        ], new PrintLogger(Builder::VERBOSITY_VERBOSE));
        $builder->setSourceDir($this->sourceDir);
        $builder->setDestinationDir($this->destinationDir);

        return new Cache($builder, 'assets');
    }
}

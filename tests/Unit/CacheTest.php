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

use Cecil\Asset;
use Cecil\Builder;
use Cecil\Cache;
use Cecil\Logger\PrintLogger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\SplFileInfo;

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

    public function testGetReturnsDefaultWhenEntryDoesNotExist(): void
    {
        $cache = $this->createCache();

        self::assertSame('fallback', $cache->get('missing-key', 'fallback'));
    }

    public function testExpiredEntryReturnsDefaultAndIsDeleted(): void
    {
        $cache = $this->createCache();
        $key = $cache->createKey('expirable', name: 'ttl-test');

        self::assertTrue($cache->set($key, ['value' => 'x'], 0));

        self::assertSame('fallback', $cache->get($key, 'fallback'));
        self::assertFalse($cache->has($key));
    }

    public function testCreateKeyRejectsUnsupportedValueType(): void
    {
        $cache = $this->createCache();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('invalid value type');

        $cache->createKey(new \stdClass());
    }

    public function testCreateKeyIncludesTruthyTagsAndSkipsFalsyOnes(): void
    {
        $cache = $this->createCache();
        $key = $cache->createKey('stylesheet', name: 'asset', tags: [
            'minify' => true,
            'lang' => 'fr',
            'weight' => 0,
            'debug' => false,
        ]);

        self::assertStringContainsString('asset-', $key);
        self::assertStringContainsString('_minify_lfr__', $key);
        self::assertStringNotContainsString('weight', $key);
        self::assertStringNotContainsString('debug', $key);
    }

    public function testClearByPatternReturnsZeroWhenCacheDirectoryDoesNotExist(): void
    {
        $cache = $this->createCache();
        $cache->clear();

        self::assertSame(0, $cache->clearByPattern('anything'));
    }

    public function testGetContentFileStripsProtocolFromPath(): void
    {
        $cache = $this->createCache();

        $path = $cache->getContentFile('https://example.com/assets/style.css');

        self::assertStringNotContainsString('https://', $path);
        self::assertStringContainsString('example.com', $path);
    }

    public function testSetAndGetWithDateIntervalTtl(): void
    {
        $cache = $this->createCache();
        $key = $cache->createKey('interval', name: 'interval');

        self::assertTrue($cache->set($key, 'value', new \DateInterval('PT10S')));
        self::assertSame('value', $cache->get($key));
    }

    public function testSetPrunesPreviousEntriesWithSamePrefix(): void
    {
        $cache = $this->createCache();
        $first = 'shared__hash-1__v1';
        $second = 'shared__hash-2__v1';

        self::assertTrue($cache->set($first, 'first'));
        self::assertTrue($cache->set($second, 'second'));

        self::assertSame('fallback', $cache->get($first, 'fallback'));
        self::assertSame('second', $cache->get($second));
    }

    public function testCreateKeyCreatesShardedCacheFilePath(): void
    {
        $cache = $this->createCache();
        $key = $cache->createKey('content', name: 'assets-image');

        self::assertTrue($cache->set($key, 'value'));
        self::assertTrue($cache->has($key));
        self::assertSame('value', $cache->get($key));
    }

    public function testCreateKeyFromAssetUsesPathExtensionAndHash(): void
    {
        $cache = $this->createCache();

        $asset = new class () extends Asset {
            public function __construct()
            {
            }

            public function seed(array $data): self
            {
                $this->data = $data;

                return $this;
            }
        };

        $asset->seed([
            '_path' => '/css/app.css',
            'ext' => 'css',
            'hash' => 'abc123',
        ]);

        $key = $cache->createKey($asset);

        self::assertStringContainsString('css-app.css_css__abc123__', $key);
    }

    public function testCreateKeyFromSplFileInfoUsesRelativePathAndFileHash(): void
    {
        $cache = $this->createCache();

        $fileDir = $this->sourceDir . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'css';
        $this->filesystem->mkdir($fileDir);
        $filePath = $fileDir . DIRECTORY_SEPARATOR . 'app.css';
        file_put_contents($filePath, 'body{color:black;}');

        $file = new SplFileInfo($filePath, 'assets/css', 'assets/css/app.css');
        $key = $cache->createKey($file);

        self::assertStringContainsString('assets-css-app.css__', $key);
        self::assertStringContainsString('__' . Builder::getVersion(), $key);
    }

    public function testGetMultipleIsNotImplemented(): void
    {
        $cache = $this->createCache();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cache::getMultiple not yet implemented.');

        $cache->getMultiple(['a', 'b']);
    }

    public function testSetMultipleIsNotImplemented(): void
    {
        $cache = $this->createCache();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cache::setMultiple not yet implemented.');

        $cache->setMultiple(['a' => 1]);
    }

    public function testDeleteMultipleIsNotImplemented(): void
    {
        $cache = $this->createCache();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cache::deleteMultiple not yet implemented.');

        $cache->deleteMultiple(['a']);
    }

    public function testDeleteAndClearAreSafeOnMissingEntries(): void
    {
        $cache = $this->createCache();

        self::assertTrue($cache->delete('missing-key'));
        self::assertTrue($cache->clear());
    }

    public function testGetReturnsPayloadWithoutContentWhenContentFileIsMissing(): void
    {
        $cache = $this->createCache();
        $key = $cache->createKey('stylesheet', name: 'missing-content');
        $contentPath = 'styles/missing.css';

        self::assertTrue($cache->set($key, [
            'content' => 'body { color: green; }',
            'path'    => $contentPath,
        ]));

        $this->filesystem->remove($cache->getContentFile($contentPath));
        $value = $cache->get($key);

        self::assertIsArray($value);
        self::assertSame($contentPath, $value['path']);
        self::assertArrayNotHasKey('content', $value);
    }

    public function testHasReflectsEntryLifecycle(): void
    {
        $cache = $this->createCache();
        $key = $cache->createKey('lifecycle', name: 'lifecycle');

        self::assertFalse($cache->has($key));
        self::assertTrue($cache->set($key, 'value'));
        self::assertTrue($cache->has($key));
        self::assertTrue($cache->delete($key));
        self::assertFalse($cache->has($key));
    }

    public function testGetRestoresContentFromDedicatedContentFile(): void
    {
        $cache = $this->createCache();
        $key = $cache->createKey('stylesheet', name: 'content-restore');

        self::assertTrue($cache->set($key, [
            'content' => 'body { color: purple; }',
            'path' => 'styles/restore.css',
        ]));

        $value = $cache->get($key);

        self::assertIsArray($value);
        self::assertSame('styles/restore.css', $value['path']);
        self::assertSame('body { color: purple; }', $value['content']);
    }

    public function testDurationHelperSupportsIntAndDateInterval(): void
    {
        $cache = $this->createTestableCache();

        self::assertSame(42, $cache->durationPublic(42));
        self::assertSame(3661, $cache->durationPublic(new \DateInterval('PT1H1M1S')));
    }

    public function testDeleteContentFileRemovesExistingFileAndIsIdempotent(): void
    {
        $cache = $this->createTestableCache();
        $path = 'styles/delete-me.css';
        $file = $cache->getContentFile($path);
        $this->filesystem->dumpFile($file, 'body{}');

        self::assertFileExists($file);
        self::assertTrue($cache->deleteContentFilePublic($path));
        self::assertFileDoesNotExist($file);
        self::assertTrue($cache->deleteContentFilePublic($path));
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

    private function createTestableCache(): Cache
    {
        $builder = new Builder([
            'baseurl' => 'https://example.com/',
        ], new PrintLogger(Builder::VERBOSITY_VERBOSE));
        $builder->setSourceDir($this->sourceDir);
        $builder->setDestinationDir($this->destinationDir);

        return new class ($builder, 'assets') extends Cache {
            public function durationPublic(int|\DateInterval $ttl): int
            {
                return $this->duration($ttl);
            }

            public function deleteContentFilePublic(string $path): bool
            {
                return $this->deleteContentFile($path);
            }
        };
    }
}

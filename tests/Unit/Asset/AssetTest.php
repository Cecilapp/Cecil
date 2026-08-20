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

namespace Cecil\Test\Unit\Asset;

use Cecil\Asset;
use Cecil\Builder;
use Cecil\Cache;
use Cecil\Config;
use Cecil\Exception\RuntimeException;
use Cecil\Logger\PrintLogger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class AssetTest extends TestCase
{
    private string $root;

    private string $sourceDir;

    private string $destinationDir;

    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cecil-asset-test-' . uniqid('', true);
        $this->sourceDir = $this->root . DIRECTORY_SEPARATOR . 'source';
        $this->destinationDir = $this->root . DIRECTORY_SEPARATOR . 'destination';
        $this->filesystem->mkdir([$this->sourceDir, $this->destinationDir]);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->root);
    }

    public function testArrayAccessMethodsManipulateData(): void
    {
        ['asset' => $asset] = $this->createTestAsset();

        $asset['foo'] = 'bar';
        self::assertTrue(isset($asset['foo']));
        self::assertSame('bar', $asset['foo']);

        unset($asset['foo']);
        self::assertFalse(isset($asset['foo']));
        self::assertNull($asset['foo']);
    }

    public function testSaveReturnsEarlyWhenAssetIsMissing(): void
    {
        ['asset' => $asset, 'builder' => $builder] = $this->createTestAsset([
            'missing' => true,
            'path' => '/styles/missing.css',
        ]);

        $asset->save();

        self::assertSame([], $builder->getAssetsList());
    }

    public function testSaveThrowsWhenPathIsMissing(): void
    {
        ['asset' => $asset] = $this->createTestAsset([
            'missing' => false,
            'path' => '',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('missing path');

        $asset->save();
    }

    public function testSaveAddsAssetWhenContentFileAlreadyExists(): void
    {
        ['asset' => $asset, 'builder' => $builder, 'cache' => $cache] = $this->createTestAsset([
            'path' => '/styles/app.css',
            'content' => 'body{}',
        ]);

        $contentFile = $cache->getContentFile('/styles/app.css');
        $this->filesystem->dumpFile($contentFile, 'body{}');

        $asset->save();

        self::assertSame(['/styles/app.css'], $builder->getAssetsList());
    }

    public function testSaveRebuildsMissingContentCacheFileWhenContentIsAvailable(): void
    {
        ['asset' => $asset, 'builder' => $builder, 'cache' => $cache] = $this->createTestAsset([
            'path' => '/styles/rebuild.css',
            'content' => 'a{b:c;}',
        ]);

        $contentFile = $cache->getContentFile('/styles/rebuild.css');
        $this->filesystem->remove($contentFile);

        $asset->save();

        self::assertFileExists($contentFile);
        self::assertSame(['/styles/rebuild.css'], $builder->getAssetsList());
    }

    public function testSaveSkipsAssetWhenContentCacheFileCannotBeRebuilt(): void
    {
        ['asset' => $asset, 'builder' => $builder, 'cache' => $cache] = $this->createTestAsset([
            'path' => '/styles/skip.css',
            'content' => '',
        ]);

        $contentFile = $cache->getContentFile('/styles/skip.css');
        $this->filesystem->remove($contentFile);

        $asset->save();

        self::assertFileDoesNotExist($contentFile);
        self::assertSame([], $builder->getAssetsList());
    }

    public function testDataurlAndIntegrityHelpers(): void
    {
        ['asset' => $asset] = $this->createTestAsset([
            'type' => 'text',
            'subtype' => 'text/plain',
            'content' => 'hello',
        ]);

        self::assertSame('data:text/plain;base64,aGVsbG8=', $asset->dataurl());
        self::assertStringStartsWith('sha384-', $asset->integrity());
        self::assertStringStartsWith('sha256-', $asset->integrity('sha256'));
    }

    public function testConvertAndFormatsThrowForNonImageAsset(): void
    {
        ['asset' => $asset] = $this->createTestAsset([
            'path' => '/scripts/app.js',
            'type' => 'text',
            'ext' => 'js',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('not an image');

        $asset->convert('webp');
    }

    public function testWebpAndAvifDelegateToConvertAndThrowForNonImage(): void
    {
        ['asset' => $asset] = $this->createTestAsset([
            'path' => '/scripts/app.js',
            'type' => 'text',
            'ext' => 'js',
        ]);

        try {
            $asset->webp();
            self::fail('Expected exception for webp conversion.');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('not an image', $e->getMessage());
        }

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('not an image');

        $asset->avif();
    }

    public function testGetWidthAndHeightReturnNullForUnsupportedType(): void
    {
        ['asset' => $asset] = $this->createTestAsset([
            'type' => 'text',
        ]);

        self::assertNull($asset->getWidth());
        self::assertNull($asset->getHeight());
    }

    public function testGetVideoThrowsForNonVideoAsset(): void
    {
        ['asset' => $asset] = $this->createTestAsset([
            'path' => '/img/logo.svg',
            'type' => 'image',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to get video infos');

        $asset->getVideo();
    }

    public function testIsImageInCdnForSvgAndRemoteCases(): void
    {
        ['asset' => $svgAsset] = $this->createTestAsset([
            'type' => 'image',
            'ext' => 'svg',
            'subtype' => 'image/svg+xml',
            'url' => null,
        ], [
            'assets' => [
                'images' => [
                    'cdn' => [
                        'enabled' => true,
                        'svg' => true,
                        'remote' => false,
                    ],
                ],
            ],
        ]);
        self::assertTrue($svgAsset->isImageInCdn());

        ['asset' => $remoteAsset] = $this->createTestAsset([
            'type' => 'image',
            'ext' => 'png',
            'subtype' => 'image/png',
            'url' => 'https://example.com/a.png',
        ], [
            'assets' => [
                'images' => [
                    'cdn' => [
                        'enabled' => false,
                        'remote' => true,
                    ],
                ],
            ],
        ]);
        self::assertTrue($remoteAsset->isImageInCdn());

        ['asset' => $localPng] = $this->createTestAsset([
            'type' => 'image',
            'ext' => 'png',
            'subtype' => 'image/png',
            'url' => null,
        ], [
            'assets' => [
                'images' => [
                    'cdn' => [
                        'enabled' => true,
                        'svg' => false,
                        'remote' => false,
                    ],
                ],
            ],
        ]);
        self::assertFalse($localPng->isImageInCdn());
    }

    public function testStaticHelpersDelegateToLocator(): void
    {
        self::assertSame(
            \Cecil\Asset\Locator::buildPathFromUrl('https://example.com/style.css'),
            Asset::buildPathFromUrl('https://example.com/style.css')
        );
        self::assertSame(
            \Cecil\Asset\Locator::sanitize('a<b>c'),
            Asset::sanitize('a<b>c')
        );
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $config
     *
     * @return array{asset: Asset, builder: Builder, cache: Cache}
     */
    private function createTestAsset(array $data = [], array $config = []): array
    {
        $builder = new Builder(array_merge([
            'baseurl' => 'https://example.com/',
        ], $config), new PrintLogger(Builder::VERBOSITY_VERBOSE));
        $builder->setSourceDir($this->sourceDir);
        $builder->setDestinationDir($this->destinationDir);
        $cache = new Cache($builder, 'assets');

        $asset = new class () extends Asset {
            public function __construct()
            {
            }

            public function seed(Builder $builder, Config $config, Cache $cache, array $data): void
            {
                $this->builder = $builder;
                $this->config = $config;
                $this->cache = $cache;
                $this->data = $data;
                $this->cacheTags = [];
            }
        };

        $asset->seed($builder, $builder->getConfig(), $cache, array_merge([
            'file' => '',
            'files' => [],
            'missing' => false,
            '_path' => '/asset',
            'path' => '/asset',
            'url' => null,
            'ext' => 'txt',
            'type' => 'text',
            'subtype' => 'text/plain',
            'size' => 0,
            'width' => null,
            'height' => null,
            'exif' => [],
            'duration' => null,
            'content' => 'content',
            'hash' => 'hash',
        ], $data));

        return [
            'asset' => $asset,
            'builder' => $builder,
            'cache' => $cache,
        ];
    }
}

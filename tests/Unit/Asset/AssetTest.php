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
use Cecil\Collection\Page\Collection as PagesCollection;
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

    public function testToStringReturnsAssetPathByDefault(): void
    {
        ['asset' => $asset, 'cache' => $cache] = $this->createTestAsset([
            'path' => '/css/site.css',
            'content' => 'body{color:black;}',
        ]);
        $this->filesystem->dumpFile($cache->getContentFile('/css/site.css'), 'body{color:black;}');

        self::assertSame('/css/site.css', (string) $asset);
    }

    public function testToStringReturnsCanonicalUrlWhenEnabled(): void
    {
        ['asset' => $asset, 'cache' => $cache] = $this->createTestAsset([
            'path' => '/css/site.css',
            'content' => 'body{color:black;}',
        ], [
            'canonicalurl' => true,
            'baseurl' => 'https://example.test/base/',
        ]);
        $this->filesystem->dumpFile($cache->getContentFile('/css/site.css'), 'body{color:black;}');

        self::assertSame('https://example.test/base/css/site.css', (string) $asset);
    }

    public function testToStringReturnsImageCdnUrlWhenEnabledForSvg(): void
    {
        ['asset' => $asset, 'cache' => $cache] = $this->createTestAsset([
            'path' => '/img/logo.svg',
            'type' => 'image',
            'ext' => 'svg',
            'subtype' => 'image/svg+xml',
            'width' => 120,
            'height' => 60,
            'content' => '<svg width="120" height="60" xmlns="http://www.w3.org/2000/svg"/>',
        ], [
            'assets' => [
                'images' => [
                    'quality' => 80,
                    'cdn' => [
                        'enabled' => true,
                        'svg' => true,
                        'remote' => false,
                        'account' => 'demo-account',
                        'canonical' => true,
                        'url' => 'https://cdn.example/%account%/%image_url%?w=%width%&q=%quality%&f=%format%',
                    ],
                ],
            ],
        ]);
        $this->filesystem->dumpFile($cache->getContentFile('/img/logo.svg'), $asset['content']);

        $url = (string) $asset;

        self::assertStringStartsWith('https://cdn.example/demo-account/', $url);
        self::assertStringContainsString('w=120', $url);
        self::assertStringContainsString('q=80', $url);
        self::assertStringContainsString('f=svg', $url);
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

    public function testConvertReturnsUpdatedCloneWhenImageHandledByCdn(): void
    {
        ['asset' => $asset] = $this->createTestAsset([
            'path' => '/img/photo.png',
            'type' => 'image',
            'ext' => 'png',
            'subtype' => 'image/png',
            'url' => 'https://example.com/photo.png',
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

        $converted = $asset->convert('webp', 42);

        self::assertNotSame($asset, $converted);
        self::assertSame('webp', $converted['ext']);
        self::assertSame('image/webp', $converted['subtype']);
        self::assertSame('/img/photo.png', $converted['path']);
        self::assertSame('/img/photo.png', $asset['path']);
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

    public function testGetWidthThrowsOnInvalidImageContent(): void
    {
        ['asset' => $asset] = $this->createTestAsset([
            'path' => '/img/invalid.png',
            'type' => 'image',
            'ext' => 'png',
            'subtype' => 'image/png',
            'content' => 'invalid-image-content',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to get width');

        $asset->getWidth();
    }

    public function testGetHeightThrowsOnInvalidImageContent(): void
    {
        ['asset' => $asset] = $this->createTestAsset([
            'path' => '/img/invalid.png',
            'type' => 'image',
            'ext' => 'png',
            'subtype' => 'image/png',
            'content' => 'invalid-image-content',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to get height');

        $asset->getHeight();
    }

    public function testGetWidthAndHeightFromSvgAttributes(): void
    {
        ['asset' => $asset] = $this->createTestAsset([
            'path' => '/img/vector.svg',
            'type' => 'image',
            'ext' => 'svg',
            'subtype' => 'image/svg+xml',
            'content' => '<svg width="321" height="123" xmlns="http://www.w3.org/2000/svg"></svg>',
        ]);

        self::assertSame(321, $asset->getWidth());
        self::assertSame(123, $asset->getHeight());
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

    public function testGetAudioThrowsWhenFileIsInvalid(): void
    {
        ['asset' => $asset] = $this->createTestAsset([
            'path' => '/audio/test.mp3',
            'type' => 'audio',
            'file' => $this->root . DIRECTORY_SEPARATOR . 'missing.mp3',
        ]);

        $this->expectException(\Throwable::class);

        $asset->getAudio();
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

    public function testResizeReturnsSameAssetForShortCircuitConditions(): void
    {
        ['asset' => $asset] = $this->createTestAsset([
            'path' => '/img/photo.png',
            'type' => 'image',
            'ext' => 'png',
            'subtype' => 'image/png',
            'width' => 400,
            'height' => 200,
        ]);

        self::assertSame($asset, $asset->resize());
        self::assertSame($asset, $asset->resize(400, 200));
        self::assertSame($asset, $asset->resize(450, null));
        self::assertSame($asset, $asset->resize(null, 250));
    }

    public function testResizeReturnsComputedDimensionsWhenHandledByCdn(): void
    {
        ['asset' => $asset] = $this->createTestAsset([
            'path' => '/img/photo.png',
            'type' => 'image',
            'ext' => 'png',
            'subtype' => 'image/png',
            'url' => 'https://example.com/photo.png',
            'width' => 400,
            'height' => 200,
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

        $resizedByWidth = $asset->resize(100, null);
        self::assertSame(100, $resizedByWidth['width']);
        self::assertEquals(50, $resizedByWidth['height']);

        $resizedByHeight = $asset->resize(null, 50);
        self::assertEquals(100, $resizedByHeight['width']);
        self::assertSame(50, $resizedByHeight['height']);
    }

    public function testFingerprintUpdatesPathWithContentHash(): void
    {
        ['asset' => $asset] = $this->createTestAsset([
            'path' => '/css/site.css',
            'ext' => 'css',
            'content' => 'body { color: red; }',
        ]);

        $result = $asset->fingerprint();

        self::assertSame($asset, $result);
        self::assertMatchesRegularExpression('#^/css/site\.[a-f0-9]+\.css$#', $asset['path']);
    }

    public function testCompileTransformsScssToCss(): void
    {
        ['asset' => $asset] = $this->createTestAsset([
            'path' => '/css/site.scss',
            'file' => $this->sourceDir . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'site.scss',
            'ext' => 'scss',
            'type' => 'text',
            'subtype' => 'text/x-scss',
            'content' => '$color: red; body { color: $color; }',
        ]);

        $asset->compile();

        self::assertSame('/css/site.css', $asset['path']);
        self::assertSame('css', $asset['ext']);
        self::assertSame('text/css', $asset['subtype']);
        self::assertStringContainsString('body', $asset['content']);
    }

    public function testMinifyTransformsCssContent(): void
    {
        ['asset' => $asset] = $this->createTestAsset([
            'path' => '/css/site.css',
            'ext' => 'css',
            'type' => 'text',
            'subtype' => 'text/css',
            'content' => "body {\n    color: red;\n}\n",
            'size' => 0,
        ]);

        $asset->minify();

        self::assertSame('body{color:red}', trim((string) $asset['content']));
        self::assertGreaterThan(0, $asset['size']);
    }

    public function testMinifyCompilesScssBeforeMinifying(): void
    {
        ['asset' => $asset] = $this->createTestAsset([
            'path' => '/css/site.scss',
            'file' => $this->sourceDir . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'site.scss',
            'ext' => 'scss',
            'type' => 'text',
            'subtype' => 'text/x-scss',
            'content' => '$color: red; body { color: $color; }',
            'size' => 0,
        ]);

        $asset->minify();

        self::assertSame('/css/site.css', $asset['path']);
        self::assertSame('css', $asset['ext']);
        self::assertSame('text/css', $asset['subtype']);
        self::assertSame('body{color:red}', trim((string) $asset['content']));
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
        $builder->setPages(new PagesCollection('pages'));
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

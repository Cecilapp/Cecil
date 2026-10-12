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

namespace Cecil\Test\Unit\Converter;

use Cecil\Builder;
use Cecil\Collection\Page\Collection as PagesCollection;
use Cecil\Converter\Parsedown;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Filesystem\Filesystem;

class ParsedownTest extends TestCase
{
    private string $root;

    private Filesystem $filesystem;

    private Parsedown $parsedown;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cecil-parsedown-test-' . uniqid('', true);
        $sourceDir = $this->root . DIRECTORY_SEPARATOR . 'source';
        $destinationDir = $this->root . DIRECTORY_SEPARATOR . 'destination';
        $this->filesystem->mkdir([$sourceDir, $destinationDir]);
        $this->filesystem->dumpFile($sourceDir . '/assets/videos/test.mp4', 'video');
        $this->filesystem->dumpFile($sourceDir . '/assets/audio/test.mp3', 'audio');

        $builder = new Builder([
            'baseurl' => 'https://example.com/',
            'cache' => ['enabled' => false],
            'assets' => ['fingerprint' => true],
        ], new NullLogger());
        $builder->setSourceDir($sourceDir);
        $builder->setDestinationDir($destinationDir);
        $builder->setPages(new PagesCollection('pages'));
        // the build ID is normally set by Builder::build() (and resets Parsedown static caches)
        self::setBuildId(uniqid('test', true));
        $this->parsedown = new Parsedown($builder);
    }

    protected function tearDown(): void
    {
        self::setBuildId(null);
        $this->filesystem->remove($this->root);
    }

    private static function setBuildId(?string $id): void
    {
        \Closure::bind(static function (?string $id): void {
            self::$buildId = $id;
        }, null, Builder::class)($id);
    }

    public function testNotEmbeddedLocalVideoLinkTargetsTheFingerprintedAsset(): void
    {
        $html = $this->parsedown->text('[Video](/videos/test.mp4){embed=false}');

        self::assertMatchesRegularExpression('#<a href="/videos/test\.[a-f0-9]{32}\.mp4">Video</a>#', $html);
    }

    public function testNotEmbeddedLocalAudioLinkTargetsTheFingerprintedAsset(): void
    {
        $html = $this->parsedown->text('[Audio](/audio/test.mp3){embed=false}');

        self::assertMatchesRegularExpression('#<a href="/audio/test\.[a-f0-9]{32}\.mp3">Audio</a>#', $html);
    }

    public function testNotEmbeddedMissingVideoLinkIsKeptAsIs(): void
    {
        $html = $this->parsedown->text('[Video](/videos/missing.mp4){embed=false}');

        self::assertStringContainsString('<a href="/videos/missing.mp4">Video</a>', $html);
    }
}

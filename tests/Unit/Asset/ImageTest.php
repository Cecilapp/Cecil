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

use Cecil\Asset\Image;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ImageTest extends TestCase
{
    /**
     * @return array<string, array{string, string, string}>
     */
    public static function darkAssetPathProvider(): array
    {
        return [
            'root file'       => ['/screenshot.png', '.dark', '/screenshot.dark.png'],
            'nested file'     => ['/images/logo.png', '.dark', '/images/logo.dark.png'],
            'no extension'    => ['/images/logo', '.dark', '/images/logo.dark'],
            'multiple dots'   => ['/images/logo.min.svg', '-dark', '/images/logo.min-dark.svg'],
        ];
    }

    #[DataProvider('darkAssetPathProvider')]
    public function testBuildDarkAssetPath(string $assetPath, string $darkSuffix, string $expected): void
    {
        $this->assertSame($expected, Image::buildDarkAssetPath($assetPath, $darkSuffix));
    }

    public function testExtractIcoLargestIcon(): void
    {
        $data = (string) file_get_contents(__DIR__ . '/../../fixtures/website/assets/images/favicon.ico');
        $icon = Image::extractIcoLargestIcon($data);

        $this->assertStringStartsWith("\x89PNG", $icon);
        $size = getimagesizefromstring($icon);
        $this->assertNotFalse($size);
        $this->assertSame([64, 64], [$size[0], $size[1]]);
    }

    public function testExtractIcoLargestIconFromPng(): void
    {
        $png = "\x89PNG\r\n\x1a\n" . 'data';

        $this->assertSame($png, Image::extractIcoLargestIcon($png));
    }

    public function testExtractIcoLargestIconInvalid(): void
    {
        $this->expectException(\Cecil\Exception\RuntimeException::class);
        Image::extractIcoLargestIcon('not an ico');
    }

    public function testBuildIco(): void
    {
        $data = (string) file_get_contents(__DIR__ . '/../../fixtures/website/assets/images/favicon.ico');
        $icon = Image::extractIcoLargestIcon($data);
        $ico = Image::buildIco($icon, 64, 64);

        $size = getimagesizefromstring($ico);
        $this->assertNotFalse($size);
        $this->assertSame(IMAGETYPE_ICO, $size[2]);
        $this->assertSame([64, 64], [$size[0], $size[1]]);
        $this->assertSame($icon, Image::extractIcoLargestIcon($ico));
    }
}

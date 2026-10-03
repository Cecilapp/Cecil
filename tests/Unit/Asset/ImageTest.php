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

    public function testGetIcoSize(): void
    {
        // the largest icon (256x256, stored as 0) is not the first one
        $ico = pack('vvv', 0, 1, 2)
            . pack('CCCCvvVV', 16, 16, 0, 0, 1, 32, 10, 6 + 32)
            . pack('CCCCvvVV', 0, 0, 0, 0, 1, 32, 10, 6 + 32 + 10)
            . str_repeat("\0", 20);

        $this->assertSame([256, 256], Image::getIcoSize($ico));
    }

    public function testGetIcoSizeFromFixture(): void
    {
        $data = (string) file_get_contents(__DIR__ . '/../../fixtures/website/assets/images/favicon.ico');

        $this->assertSame([64, 64], Image::getIcoSize($data));
    }

    /**
     * Builds a 2x2 BMP icon (DIB data): red, green (bottom row), blue, white (top row).
     */
    private static function buildDib(int $bpp, bool $alpha): string
    {
        $pixels = [[0, 0, 255], [0, 255, 0], [255, 0, 0], [255, 255, 255]]; // BGR, bottom-up
        $rows = '';
        foreach (array_chunk($pixels, 2) as $row) {
            $data = '';
            foreach ($row as $pixel) {
                $data .= pack('C3', ...$pixel) . ($bpp == 32 ? \chr($alpha ? 128 : 0) : '');
            }
            $rows .= str_pad($data, 8, "\0"); // 4 bytes aligned
        }
        // AND mask: last pixel (top-right) is transparent
        $mask = pack('N', 0) . pack('N', 0x40000000);

        return pack('VVVvvVVVVVV', 40, 2, 4, 1, $bpp, 0, 0, 0, 0, 0, 0) . $rows . $mask;
    }

    /**
     * @return array<string, array{int, bool, int}>
     */
    public static function dibProvider(): array
    {
        return [
            '24 bits'                 => [24, false, 127],
            '32 bits with alpha'      => [32, true, 63],
            '32 bits with empty alpha' => [32, false, 127],
        ];
    }

    #[DataProvider('dibProvider')]
    public function testDibToPng(int $bpp, bool $alpha, int $topRightAlpha): void
    {
        if (!\extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is required.');
        }
        $png = Image::dibToPng(self::buildDib($bpp, $alpha));
        $this->assertNotNull($png);
        $image = imagecreatefromstring($png);
        $this->assertNotFalse($image);
        $this->assertSame([2, 2], [imagesx($image), imagesy($image)]);
        // top-left: blue, bottom-left: red (GD alpha: 0 = opaque, 127 = transparent)
        $this->assertSame(['red' => 0, 'green' => 0, 'blue' => 255], \array_slice(imagecolorsforindex($image, imagecolorat($image, 0, 0)), 0, 3));
        $this->assertSame(['red' => 255, 'green' => 0, 'blue' => 0], \array_slice(imagecolorsforindex($image, imagecolorat($image, 0, 1)), 0, 3));
        $this->assertSame($topRightAlpha, imagecolorsforindex($image, imagecolorat($image, 1, 0))['alpha']);
    }

    public function testDibToPngUnsupported(): void
    {
        $this->assertNull(Image::dibToPng(pack('VVVvvVVVVVV', 40, 2, 4, 1, 8, 0, 0, 0, 0, 0, 0) . str_repeat("\0", 64)));
    }

    public function testGetIcoSizeInvalid(): void
    {
        $this->expectException(\Cecil\Exception\RuntimeException::class);
        Image::getIcoSize('not an ico');
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

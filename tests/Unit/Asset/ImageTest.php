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
}

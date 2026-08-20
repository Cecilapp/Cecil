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

namespace Cecil\Test\Unit\Util;

use Cecil\Util\Platform;
use PHPUnit\Framework\TestCase;

class PlatformTest extends TestCase
{
    public function testIsWindowsMatchesPhpConstant(): void
    {
        self::assertSame(\defined('PHP_WINDOWS_VERSION_BUILD'), Platform::isWindows());
    }

    public function testGetOsReturnsKnownConstant(): void
    {
        $os = Platform::getOS();

        self::assertContains($os, [
            Platform::OS_UNKNOWN,
            Platform::OS_WIN,
            Platform::OS_LINUX,
            Platform::OS_OSX,
        ]);
    }

    public function testGetPharPathThrowsWhenNotRunningFromPhar(): void
    {
        if (Platform::isPhar()) {
            self::assertNotSame('', Platform::getPharPath());

            return;
        }

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unable to get Phar path.');

        Platform::getPharPath();
    }

    public function testIsPharReturnsBoolean(): void
    {
        self::assertIsBool(Platform::isPhar());
    }

    public function testGetPharPathReturnsPreconfiguredStaticPath(): void
    {
        $getter = static function () {
            return self::$pharPath ?? null;
        };
        $setter = static function ($value): void {
            self::$pharPath = $value;
        };
        $boundGetter = \Closure::bind($getter, null, Platform::class);
        $boundSetter = \Closure::bind($setter, null, Platform::class);
        $previousValue = $boundGetter();

        try {
            $boundSetter('/tmp/fake-cecil.phar');
            self::assertSame('/tmp/fake-cecil.phar', Platform::getPharPath());
        } finally {
            $boundSetter($previousValue);
        }
    }
}

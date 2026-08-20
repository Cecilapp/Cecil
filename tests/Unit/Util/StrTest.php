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

use Cecil\Util\Str;
use PHPUnit\Framework\TestCase;

class StrTest extends TestCase
{
    public function testCombineArrayToStringSupportsSeparator(): void
    {
        $value = Str::combineArrayToString([
            ['name' => 'a', 'value' => 1],
            ['name' => 'b', 'value' => 2],
        ], 'name', 'value', '=');

        self::assertSame('a=1, b=2', $value);
    }

    public function testArrayToListFormatsBulletLines(): void
    {
        self::assertSame(" - one\n - two", Str::arrayToList(['one', 'two']));
    }

    public function testStrToBoolConvertsKnownLiteralsOnly(): void
    {
        self::assertTrue(Str::strToBool('true'));
        self::assertFalse(Str::strToBool('off'));
        self::assertSame('maybe', Str::strToBool('maybe'));
        self::assertSame(42, Str::strToBool(42));
    }

    public function testStartsWithAndEndsWith(): void
    {
        self::assertTrue(Str::startsWith('cecil-app', 'cecil'));
        self::assertFalse(Str::startsWith('cecil-app', 'app'));

        self::assertTrue(Str::endsWith('cecil-app', 'app'));
        self::assertFalse(Str::endsWith('cecil-app', 'cecil'));
        self::assertTrue(Str::endsWith('cecil-app', ''));
    }
}

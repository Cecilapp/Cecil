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

namespace Cecil\Test\Unit\Collection\Page;

use Cecil\Collection\Page\PrefixSuffix;
use PHPUnit\Framework\TestCase;

class PrefixSuffixTest extends TestCase
{
    public function testPrefixAndSuffixDetection(): void
    {
        self::assertTrue(PrefixSuffix::hasPrefix('2026-08-20_post'));
        self::assertTrue(PrefixSuffix::hasPrefix('10_post'));
        self::assertFalse(PrefixSuffix::hasPrefix('post'));

        self::assertTrue(PrefixSuffix::hasSuffix('post.fr'));
        self::assertFalse(PrefixSuffix::hasSuffix('post'));
    }

    public function testPrefixAndSuffixExtraction(): void
    {
        self::assertSame('2026-08-20', PrefixSuffix::getPrefix('2026-08-20_post'));
        self::assertSame('fr', PrefixSuffix::getSuffix('post.fr'));
        self::assertNull(PrefixSuffix::getPrefix('post'));
        self::assertNull(PrefixSuffix::getSuffix('post'));
    }

    public function testSubAndSubPrefixRemoveExpectedParts(): void
    {
        self::assertSame('post', PrefixSuffix::sub('2026-08-20_post.fr'));
        self::assertSame('post.fr', PrefixSuffix::subPrefix('2026-08-20_post.fr'));
        self::assertSame('post', PrefixSuffix::sub('post'));
        self::assertSame('post', PrefixSuffix::subPrefix('post'));
    }

    public function testCustomPrefixSeparatorIsSupported(): void
    {
        self::assertTrue(PrefixSuffix::hasPrefix('10|post', ['|']));
        self::assertSame('10', PrefixSuffix::getPrefix('10|post', ['|']));
        self::assertSame('post', PrefixSuffix::sub('10|post', ['|']));
    }
}

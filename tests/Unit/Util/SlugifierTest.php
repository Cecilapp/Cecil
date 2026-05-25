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

use Cecil\Util\Slugifier;
use PHPUnit\Framework\TestCase;

class SlugifierTest extends TestCase
{
    public function testBasicSlug(): void
    {
        self::assertSame('hello-world', Slugifier::slugify('hello world'));
    }

    public function testPreservesDots(): void
    {
        self::assertSame('style.min.css', Slugifier::slugify('style.min.css'));
    }

    public function testPreservesSlashes(): void
    {
        self::assertSame('section/page', Slugifier::slugify('section/page'));
    }

    public function testPreservesUnderscores(): void
    {
        self::assertSame('my_page', Slugifier::slugify('my_page'));
    }

    public function testUppercaseIsLowercased(): void
    {
        self::assertSame('hello-world', Slugifier::slugify('Hello World'));
    }

    public function testLeadingSlashIsStripped(): void
    {
        self::assertSame('section/page', Slugifier::slugify('/section/page'));
    }

    public function testSpecialCharsAreReplaced(): void
    {
        self::assertSame('hello-world', Slugifier::slugify('hello & world'));
    }

    public function testAccentedCharacters(): void
    {
        $result = Slugifier::slugify('déjà-vu');
        self::assertSame('deja-vu', $result);
    }

    public function testIdempotent(): void
    {
        $slug = Slugifier::slugify('section/my-page.html');
        self::assertSame($slug, Slugifier::slugify($slug));
    }

    public function testSlugifyPatternConstant(): void
    {
        self::assertNotEmpty(Slugifier::SLUGIFY_PATTERN);
    }
}

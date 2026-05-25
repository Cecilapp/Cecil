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

use Cecil\Collection\Page\Page;
use Cecil\Util\Slugifier;
use PHPUnit\Framework\TestCase;

class PageTest extends TestCase
{
    public function testPageCreationWithStringId(): void
    {
        $page = new Page('test-page');
        self::assertSame('test-page', $page->getId());
    }

    public function testPageToStringReturnsId(): void
    {
        $page = new Page('my/page');
        self::assertSame('my/page', (string) $page);
    }

    public function testSlugifyDelegatesToSlugifier(): void
    {
        $input = 'Hello World';
        self::assertSame(Slugifier::slugify($input), Page::slugify($input));
    }

    public function testSlugifyPatternConstantMatchesSlugifier(): void
    {
        self::assertSame(Slugifier::SLUGIFY_PATTERN, Page::SLUGIFY_PATTERN);
    }

    public function testDefaultTypeIsPage(): void
    {
        $page = new Page('test');
        self::assertSame('page', $page->getType());
    }

    public function testVirtualByDefault(): void
    {
        $page = new Page('test');
        self::assertTrue($page->isVirtual());
    }

    public function testSetAndGetVariable(): void
    {
        $page = new Page('test');
        $page->setVariable('title', 'My Title');
        self::assertSame('My Title', $page->getVariable('title'));
    }

    public function testSetAndGetPath(): void
    {
        $page = new Page('test');
        $page->setPath('section/test');
        self::assertSame('section/test', $page->getPath());
    }
}

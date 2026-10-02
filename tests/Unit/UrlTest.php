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

namespace Cecil\Test\Unit;

use Cecil\Builder;
use Cecil\Collection\Page\Collection as PagesCollection;
use Cecil\Collection\Page\Page;
use Cecil\Url;
use PHPUnit\Framework\TestCase;

class UrlTest extends TestCase
{
    public function testEncode(): void
    {
        self::assertSame('categories/Data%20Sovereignty', Url::encode('categories/Data Sovereignty'));
        self::assertSame('caf%C3%A9/menu', Url::encode('café/menu'));
        // reserved characters and encoded sequences are preserved
        self::assertSame('search?q=a+b&p=1#top', Url::encode('search?q=a+b&p=1#top'));
        self::assertSame('a%20b', Url::encode('a%20b'));
        self::assertSame('100%25', Url::encode('100%'));
    }

    public function testStringMatchingAPageIsSlugified(): void
    {
        $builder = new Builder(['baseurl' => 'https://example.com/']);
        $builder->setPages(new PagesCollection('all-pages', [
            (new Page('categories/data-sovereignty'))->setPath('categories/data-sovereignty'),
        ]));

        self::assertSame('/categories/data-sovereignty/', (string) new Url($builder, 'categories/Data Sovereignty'));
    }

    public function testStringNotMatchingAPageIsEncoded(): void
    {
        $builder = new Builder(['baseurl' => 'https://example.com/']);
        $builder->setPages(new PagesCollection('all-pages'));

        self::assertSame('/categories/Data%20Sovereignty', (string) new Url($builder, 'categories/Data Sovereignty'));
    }
}

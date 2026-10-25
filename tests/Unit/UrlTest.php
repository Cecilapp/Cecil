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

    public function testLanguagePrefixIsNotDoubled(): void
    {
        $builder = new Builder([
            'baseurl'   => 'https://example.com/',
            'language'  => 'en',
            'languages' => [
                ['code' => 'en', 'name' => 'English', 'locale' => 'en_US'],
                ['code' => 'fr', 'name' => 'Français', 'locale' => 'fr_FR'],
            ],
        ]);
        $builder->setPages(new PagesCollection('all-pages'));

        self::assertSame('/fr/docs/', (string) new Url($builder, 'docs/', ['language' => 'fr']));
        self::assertSame('/fr/docs/', (string) new Url($builder, 'fr/docs/', ['language' => 'fr']));
        self::assertSame('/fr/docs/', (string) new Url($builder, '/fr/docs/', ['language' => 'fr']));
        self::assertSame('/fr/', (string) new Url($builder, '/fr', ['language' => 'fr']));
    }

    public function testLanguagePrefixIsNotDoubledWithBasepath(): void
    {
        $builder = new Builder([
            'baseurl'   => 'https://demo.cecil.app/test/',
            'language'  => 'en',
            'languages' => [
                ['code' => 'en', 'name' => 'English', 'locale' => 'en_US'],
                ['code' => 'fr', 'name' => 'Français', 'locale' => 'fr_FR'],
            ],
        ]);
        $builder->setPages(new PagesCollection('all-pages'));

        self::assertSame('/test/fr/docs/', (string) new Url($builder, 'docs/', ['language' => 'fr']));
        self::assertSame('/test/fr/docs/', (string) new Url($builder, 'fr/docs/', ['language' => 'fr']));
        self::assertSame('/test/fr/docs/', (string) new Url($builder, '/fr/docs/', ['language' => 'fr']));
        self::assertSame('/test/fr/', (string) new Url($builder, '/fr', ['language' => 'fr']));
        // absolute path already prefixed with base path
        self::assertSame('/test/fr/docs/', (string) new Url($builder, '/test/fr/docs/', ['language' => 'fr']));
        self::assertSame('/test/fr/docs/', (string) new Url($builder, '/test/docs/', ['language' => 'fr']));
        self::assertSame('/test/docs/', (string) new Url($builder, '/test/docs/'));
        self::assertSame('/test/', (string) new Url($builder, '/test'));
        // relative path is not considered as prefixed with base path
        self::assertSame('/test/test/docs/', (string) new Url($builder, 'test/docs/'));
        // canonical URL
        self::assertSame('https://demo.cecil.app/test/fr/docs/', (string) new Url($builder, '/fr/docs/', ['language' => 'fr', 'canonical' => true]));
    }

    /**
     * An empty value (or "/") returns the base URL with a trailing slash, like the home page URL.
     */
    public function testEmptyValueIsHomeUrl(): void
    {
        foreach (
            [
                'https://example.com/'         => ['/', 'https://example.com/'],
                'https://demo.cecil.app/test/' => ['/test/', 'https://demo.cecil.app/test/'],
            ] as $baseurl => [$expected, $expectedCanonical]
        ) {
            $builder = new Builder(['baseurl' => $baseurl]);
            $home = (new Page('index'))->setPath('');
            $builder->setPages(new PagesCollection('all-pages', [$home]));

            self::assertSame($expected, (string) new Url($builder, $home));
            self::assertSame($expected, (string) new Url($builder, '/'));
            self::assertSame($expected, (string) new Url($builder, ''));
            self::assertSame($expected, (string) new Url($builder, null));
            self::assertSame($expectedCanonical, (string) new Url($builder, '/', ['canonical' => true]));
            self::assertSame($expectedCanonical, (string) new Url($builder, $home, ['canonical' => true]));
        }
    }
}

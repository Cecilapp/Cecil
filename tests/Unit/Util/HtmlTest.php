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

use Cecil\Util\Html;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class HtmlTest extends TestCase
{
    public function testGetImageCandidatesOrdersFallbacks(): void
    {
        $html = <<<'HTML'
            <html>
            <head>
              <link rel="icon" href="/favicon.png">
              <link rel="apple-touch-icon" href="/apple-touch-icon.png">
              <script type="application/ld+json">{"@graph": [{"@type": "Article", "image": {"@type": "ImageObject", "url": "https://cdn.example.com/ld.jpg"}}]}</script>
              <meta itemprop="image" content="microdata.jpg">
              <link rel="image_src" href="image_src.jpg">
              <meta name="twitter:image" content="https://example.com/twitter.jpg">
              <meta property="og:image" content="/og.jpg">
            </head>
            <body>
              <img src="data:image/gif;base64,R0lGODlhAQABAAAAACw=">
              <main><img src="../content.jpg"></main>
            </body>
            </html>
            HTML;

        self::assertSame([
            'https://example.com/og.jpg',
            'https://example.com/twitter.jpg',
            'https://example.com/blog/image_src.jpg',
            'https://example.com/blog/microdata.jpg',
            'https://cdn.example.com/ld.jpg',
            'https://example.com/content.jpg',
            'https://example.com/apple-touch-icon.png',
            'https://example.com/favicon.png',
        ], Html::getImageCandidates($html, 'https://example.com/blog/post.html'));
    }

    public function testGetImageCandidatesUsesBaseHrefAndRemovesDuplicates(): void
    {
        $html = <<<'HTML'
            <html>
            <head>
              <base href="https://static.example.com/">
              <meta property="og:image:secure_url" content="img/og.jpg">
              <meta property="og:image" content="img/og.jpg">
            </head>
            <body></body>
            </html>
            HTML;

        self::assertSame(
            ['https://static.example.com/img/og.jpg'],
            Html::getImageCandidates($html, 'https://example.com/page')
        );
    }

    public function testGetImageCandidatesReturnsEmptyArrayWhenNoImage(): void
    {
        self::assertSame([], Html::getImageCandidates('<html><body><p>No image</p></body></html>', 'https://example.com/'));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function absoluteUrlsProvider(): array
    {
        return [
            'link'                      => ['<a href="/about/">About</a>', '<a href="https://example.com/about/">About</a>'],
            'image'                     => ['<img src="/images/photo.png" alt="Photo">', '<img src="https://example.com/images/photo.png" alt="Photo">'],
            'single quotes'             => ["<a href='/about/'>About</a>", "<a href='https://example.com/about/'>About</a>"],
            'unquoted'                  => ['<a href=/about/>About</a>', '<a href=https://example.com/about/>About</a>'],
            'uppercase attribute'       => ['<A HREF="/about/">About</A>', '<A HREF="https://example.com/about/">About</A>'],
            'root'                      => ['<a href="/">Home</a>', '<a href="https://example.com/">Home</a>'],
            'srcset'                    => [
                '<img src="/a.png" srcset="/a-320.png 320w, /a-640.png 640w,/a.png 1x">',
                '<img src="https://example.com/a.png" srcset="https://example.com/a-320.png 320w, https://example.com/a-640.png 640w,https://example.com/a.png 1x">',
            ],
            'srcset without descriptor' => ['<source srcset="/a.webp, /b.webp 2x">', '<source srcset="https://example.com/a.webp, https://example.com/b.webp 2x">'],
            'srcset with data URL'      => ['<img srcset="data:image/jpeg;base64,/9j/AA,AA 1x, /a.png 2x">', '<img srcset="data:image/jpeg;base64,/9j/AA,AA 1x, https://example.com/a.png 2x">'],
            'absolute URL'              => ['<a href="https://example.org/x">x</a>', '<a href="https://example.org/x">x</a>'],
            'protocol-relative URL'     => ['<a href="//example.org/x">x</a>', '<a href="//example.org/x">x</a>'],
            'anchor'                    => ['<a href="#notes">x</a>', '<a href="#notes">x</a>'],
            'mailto'                    => ['<a href="mailto:a@example.com">x</a>', '<a href="mailto:a@example.com">x</a>'],
            'data URL'                  => ['<img src="data:image/gif;base64,R0lGOD/lh">', '<img src="data:image/gif;base64,R0lGOD/lh">'],
            'relative path'             => ['<a href="about/">x</a>', '<a href="about/">x</a>'],
            'other attributes'          => ['<img data-src="/a.png" alt="/a.png">', '<img data-src="/a.png" alt="/a.png">'],
            'attribute in a value'      => ['<img alt=\'src="/a.png"\' title="x">', '<img alt=\'src="/a.png"\' title="x">'],
            'escaped code'              => ['<code>&lt;a href="/about/"&gt;</code>', '<code>&lt;a href="/about/"&gt;</code>'],
            'text'                      => ['<p>src="/a.png"</p>', '<p>src="/a.png"</p>'],
        ];
    }

    #[DataProvider('absoluteUrlsProvider')]
    public function testAbsoluteUrls(string $html, string $expected): void
    {
        self::assertSame($expected, Html::absoluteUrls($html, 'https://example.com/'));
    }

    public function testAbsoluteUrlsUsesTheOriginOfTheBaseUrl(): void
    {
        // root-relative URLs of the content already contain the path of the base URL
        self::assertSame(
            '<a href="https://example.com/blog/about/">About</a>',
            Html::absoluteUrls('<a href="/blog/about/">About</a>', 'https://example.com/blog/')
        );
        self::assertSame(
            '<a href="http://localhost:8000/about/">About</a>',
            Html::absoluteUrls('<a href="/about/">About</a>', 'http://localhost:8000')
        );
    }

    public function testAbsoluteUrlsWithoutAbsoluteBaseUrl(): void
    {
        self::assertSame('<a href="/about/">About</a>', Html::absoluteUrls('<a href="/about/">About</a>', '/'));
        self::assertSame('<a href="/about/">About</a>', Html::absoluteUrls('<a href="/about/">About</a>', ''));
    }
}

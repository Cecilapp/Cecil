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
}

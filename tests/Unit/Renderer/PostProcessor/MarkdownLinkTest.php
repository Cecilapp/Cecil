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

namespace Cecil\Test\Unit\Renderer\PostProcessor;

use Cecil\Builder;
use Cecil\Collection\Page\Collection as PagesCollection;
use Cecil\Collection\Page\Page;
use Cecil\Renderer\PostProcessor\MarkdownLink;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MarkdownLinkTest extends TestCase
{
    /**
     * @return array<string, array{array<string, mixed>, string, string, string}>
     */
    public static function linksProvider(): array
    {
        $root = ['baseurl' => 'https://example.com/'];
        $basepath = ['baseurl' => 'https://example.com/sub/'];
        $canonical = ['baseurl' => 'https://example.com/sub/', 'canonicalurl' => true];

        return [
            'index to child'                  => [$root, 'docs/index.md', 'guide.md', '/docs/guide/'],
            'basepath: index to child'        => [$basepath, 'docs/index.md', 'guide.md', '/sub/docs/guide/'],
            'basepath: index to sub-page'     => [$basepath, 'docs/index.md', 'sub/page.md#anchor', '/sub/docs/sub/page/#anchor'],
            'basepath: page to parent'        => [$basepath, 'docs/guide.md', '../about.md', '/sub/about/'],
            'basepath: page to section index' => [$basepath, 'docs/guide.md', 'index.md', '/sub/docs/'],
            'basepath: root link'             => [$basepath, 'docs/guide.md', '/about.md', '/sub/about/'],
            'basepath: page not found'        => [$basepath, 'docs/index.md', 'missing.md', '/sub/docs/missing/'],
            'canonical: index to child'       => [$canonical, 'docs/index.md', 'guide.md', 'https://example.com/sub/docs/guide/'],
        ];
    }

    /**
     * @param array<string, mixed> $config
     */
    #[DataProvider('linksProvider')]
    public function testLinkIsResolvedFromSourceFileFolder(array $config, string $source, string $link, string $expected): void
    {
        $builder = new Builder($config);
        $builder->setPages(new PagesCollection('all-pages', [
            (new Page('docs'))->setPath('docs'),
            (new Page('docs/guide'))->setPath('docs/guide'),
            (new Page('docs/sub/page'))->setPath('docs/sub/page'),
            (new Page('about'))->setPath('about'),
        ]));
        $page = (new Page(Page::createIdFromPath($source)))->setVariable('filepath', $source);

        $output = (new MarkdownLink($builder))->process($page, \sprintf('<a href="%s">link</a>', $link), 'html');

        self::assertSame(\sprintf('<a href="%s">link</a>', $expected), $output);
    }
}

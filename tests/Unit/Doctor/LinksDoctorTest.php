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

namespace Cecil\Test\Unit\Doctor;

use Cecil\Builder;
use Cecil\Collection\Page\Collection as PagesCollection;
use Cecil\Collection\Page\Page;
use Cecil\Doctor\Links\UrlCheckerInterface;
use Cecil\Doctor\LinksDoctor;
use Cecil\Exception\RuntimeException;
use Cecil\Util;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Filesystem\Filesystem;

class LinksDoctorTest extends TestCase
{
    private string $sourceDir;

    protected function setUp(): void
    {
        $this->sourceDir = Util::joinFile(sys_get_temp_dir(), 'cecil-links-doctor-' . uniqid());
        (new Filesystem())->mkdir(Util::joinFile($this->sourceDir, 'static', 'css'));
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->sourceDir);
    }

    public function testValidInternalLinksHaveNoFindings(): void
    {
        file_put_contents(Util::joinFile($this->sourceDir, 'static', 'favicon.ico'), '');
        $builder = $this->builder([
            $this->page('', '<a href="blog/post/">Post</a><a href="/blog/post/#title">Title</a><a href="#top">Top</a><a href="#main">Main</a><main id="main"></main>'),
            $this->page('blog/post', '<h1 id="title">Post</h1><a href="../../">Home</a><a href="./">Self</a><a href="/?page=2">Query</a><link rel="icon" href="/favicon.ico"><script src="/js/app.js"></script>'),
        ]);
        $builder->addToAssetsList('/js/app.js');

        $report = (new LinksDoctor($this->checker()))->audit($builder);

        self::assertSame([
            'pages_audited' => 2,
            'links_checked' => 9,
            'external_urls' => 0,
            'bad_count' => 0,
            'ok_count' => 0,
            'feedback_count' => 0,
        ], $report['summary']);
        self::assertSame([], $report['findings']);
    }

    public function testBrokenInternalLinksAreReported(): void
    {
        $builder = $this->builder([
            $this->page('', '<a href="/missing/">Missing</a><a href="/blog/post#nope">Anchor</a><a href="#nope">Self</a><a href="/blog/post">No slash</a><img src="img/missing.png">'),
            $this->page('blog/post', '<h1 id="title">Post</h1>'),
        ]);

        $report = (new LinksDoctor($this->checker()))->audit($builder);

        self::assertSame([
            ['page' => '/', 'level' => 'bad', 'type' => 'internal', 'link' => '/missing/', 'details' => 'Target "/missing/" not found.'],
            ['page' => '/', 'level' => 'ok', 'type' => 'internal', 'link' => '/blog/post#nope', 'details' => 'Missing trailing slash: the server may redirect to "/blog/post/".'],
            ['page' => '/', 'level' => 'bad', 'type' => 'anchor', 'link' => '/blog/post#nope', 'details' => 'Anchor "#nope" not found in "/blog/post".'],
            ['page' => '/', 'level' => 'bad', 'type' => 'anchor', 'link' => '#nope', 'details' => 'Anchor "#nope" not found in "/".'],
            ['page' => '/', 'level' => 'ok', 'type' => 'internal', 'link' => '/blog/post', 'details' => 'Missing trailing slash: the server may redirect to "/blog/post/".'],
            ['page' => '/', 'level' => 'bad', 'type' => 'internal', 'link' => 'img/missing.png', 'details' => 'Target "/img/missing.png" not found.'],
        ], $report['findings']);
        self::assertSame(4, $report['summary']['bad_count']);
        self::assertSame(2, $report['summary']['ok_count']);
    }

    public function testAbsoluteUrlsToTheSiteAreInternal(): void
    {
        $builder = $this->builder(
            [$this->page('', '<link rel="canonical" href="https://example.com/blog/"><a href="https://EXAMPLE.com/blog/missing/">Missing</a><a href="/blog/">Home</a><a href="https://example.com/other/">Other</a>')],
            ['baseurl' => 'https://example.com/blog/']
        );

        $report = (new LinksDoctor($this->checker()))->audit($builder);

        self::assertSame(3, $report['summary']['links_checked']);
        self::assertSame(
            [['page' => '/', 'level' => 'bad', 'type' => 'internal', 'link' => 'https://EXAMPLE.com/blog/missing/', 'details' => 'Target "/missing/" not found.']],
            $report['findings']
        );
    }

    public function testExternalLinksAreNotCheckedByDefault(): void
    {
        $checker = $this->checker();

        $report = (new LinksDoctor($checker))->audit($this->builder([$this->page('', '<a href="https://example.org/">Example</a>')]));

        self::assertSame([], $checker->checked);
        self::assertSame(0, $report['summary']['links_checked']);
    }

    public function testExternalLinksAreReportedByLevel(): void
    {
        $checker = $this->checker([
            'https://example.org/ok' => ['status' => 200],
            'https://example.org/404' => ['status' => 404],
            'http://example.org/http' => ['status' => 200, 'redirect' => 'https://example.org/http'],
            'https://example.org/moved' => ['status' => 200, 'redirect' => 'https://example.org/new'],
            'https://example.org/blocked' => ['status' => 403],
            'https://example.org/slow' => ['status' => 0, 'errno' => 28, 'error' => 'Operation timed out'],
            'https://no-such-host.invalid/' => ['status' => 0, 'errno' => 6, 'error' => 'Could not resolve host'],
        ]);
        $html = '<a href="https://example.org/ok#section">OK</a><a href="https://example.org/ok">OK</a><a href="https://example.org/404">404</a>'
            . '<a href="http://example.org/http">HTTP</a><a href="https://example.org/moved">Moved</a><a href="https://example.org/blocked">Blocked</a>'
            . '<a href="https://example.org/slow">Slow</a><a href="//no-such-host.invalid/">DNS</a>'
            . '<a href="mailto:me@example.org">Mail</a><a href="tel:+33600000000">Tel</a><a href="javascript:void(0)">JS</a><img src="data:image/png;base64,AAAA">'
            . '<link rel="preconnect" href="https://fonts.example.org">';
        $builder = $this->builder([$this->page('', $html)]);

        $report = (new LinksDoctor($checker))->audit($builder, ['internal' => false, 'external' => true, 'feedback' => true, 'cache' => false]);

        self::assertCount(7, $checker->checked);
        self::assertSame(['timeout' => 10, 'concurrency' => 10, 'useragent' => 'Cecil test'], $checker->options);
        self::assertSame(8, $report['summary']['links_checked']);
        self::assertSame(7, $report['summary']['external_urls']);
        self::assertSame([
            'https://example.org/404' => ['bad', 'HTTP 404.'],
            'http://example.org/http' => ['ok', 'Redirects to HTTPS: "https://example.org/http".'],
            'https://example.org/moved' => ['ok', 'Redirects to "https://example.org/new".'],
            'https://example.org/blocked' => ['feedback', 'HTTP 403: access denied, possibly by a bot protection.'],
            'https://example.org/slow' => ['feedback', 'Timeout: Operation timed out'],
            'https://no-such-host.invalid/' => ['bad', 'Could not resolve host'],
        ], array_combine(
            array_column($report['findings'], 'link'),
            array_map(static fn (array $finding): array => [$finding['level'], $finding['details']], $report['findings'])
        ));

        $report = (new LinksDoctor($checker))->audit($builder, ['internal' => false, 'external' => true, 'cache' => false]);
        self::assertSame(0, $report['summary']['feedback_count']);
        self::assertNotContains('feedback', array_column($report['findings'], 'level'));
    }

    public function testExternalLinksConfigurationAndCache(): void
    {
        $checker = $this->checker(['https://example.org/' => ['status' => 200], 'https://example.org/404' => ['status' => 404]]);
        $builder = $this->builder(
            [$this->page('', '<a href="https://example.org/">OK</a><a href="https://example.org/404">404</a><a href="https://excluded.example.org/">Excluded</a>')],
            ['doctor' => ['links' => ['internal' => false, 'external' => true, 'timeout' => 3, 'concurrency' => 2, 'exclude' => ['^https://excluded\.']]]]
        );

        (new LinksDoctor($checker))->audit($builder);
        self::assertSame(['https://example.org/', 'https://example.org/404'], $checker->checked);
        self::assertSame(3, $checker->options['timeout']);
        self::assertSame(2, $checker->options['concurrency']);

        // successful results are cached, failures are checked again
        $checker->checked = [];
        $report = (new LinksDoctor($checker))->audit($builder);
        self::assertSame(['https://example.org/404'], $checker->checked);
        self::assertSame(1, $report['summary']['bad_count']);

        $checker->checked = [];
        (new LinksDoctor($checker))->audit($builder, ['cache' => false]);
        self::assertSame(['https://example.org/', 'https://example.org/404'], $checker->checked);
    }

    public function testCssLinksAreChecked(): void
    {
        file_put_contents(Util::joinFile($this->sourceDir, 'static', 'css', 'style.css'), <<<'CSS'
            @import "theme.css";
            @import url('missing.css');
            /* url(commented.png) */
            body { background: url(../img/bg.png); }
            .logo { background-image: url("data:image/svg+xml;utf8,<svg></svg>"); }
            @font-face { src: url('https://fonts.example.org/font.woff2'); }
            CSS);
        file_put_contents(Util::joinFile($this->sourceDir, 'static', 'css', 'theme.css'), '.a { background: url(/img/theme.png) }');
        $checker = $this->checker(['https://fonts.example.org/font.woff2' => ['status' => 404]]);
        $builder = $this->builder([$this->page('', '<link rel="stylesheet" href="/css/style.css?v=1"><style>.b { background: url(/img/inline.png) }</style><div style="background: url(\'/img/attr.png\')"></div>')]);

        $report = (new LinksDoctor($checker))->audit($builder, ['external' => true, 'cache' => false]);

        self::assertSame([
            ['/', 'internal', '/img/inline.png', 'Target "/img/inline.png" not found.'],
            ['/', 'internal', '/img/attr.png', 'Target "/img/attr.png" not found.'],
            ['/css/style.css', 'internal', 'missing.css', 'Target "/css/missing.css" not found.'],
            ['/css/style.css', 'internal', '../img/bg.png', 'Target "/img/bg.png" not found.'],
                        ['/css/theme.css', 'internal', '/img/theme.png', 'Target "/img/theme.png" not found.'],
['/css/style.css', 'external', 'https://fonts.example.org/font.woff2', 'HTTP 404.'],
        ], array_map(static fn (array $finding): array => [$finding['page'], $finding['type'], $finding['link'], $finding['details']], $report['findings']));
    }

    public function testSrcsetAndStaticTargetAreSupported(): void
    {
        (new Filesystem())->mkdir(Util::joinFile($this->sourceDir, 'static', 'img'));
        file_put_contents(Util::joinFile($this->sourceDir, 'static', 'img', 'a.png'), '');
        $builder = $this->builder(
            [$this->page('', '<img src="/files/img/a.png" srcset="/files/img/a.png 1x, /files/img/b.png 2x"><picture><source srcset="/files/img/c.webp"></picture>')],
            ['static' => ['target' => 'files']]
        );

        $report = (new LinksDoctor($this->checker()))->audit($builder);

        self::assertSame(['/files/img/b.png', '/files/img/c.webp'], array_column($report['findings'], 'link'));
    }

    public function testExcludedLinksAreIgnored(): void
    {
        $builder = $this->builder([$this->page('', '<a href="/missing/">Missing</a><a href="/drafts/x/">Draft</a>')]);

        $report = (new LinksDoctor($this->checker()))->audit($builder, ['exclude' => ['^/drafts/']]);

        self::assertSame(['/missing/'], array_column($report['findings'], 'link'));
        self::assertSame(1, $report['summary']['links_checked']);
    }

    public function testInvalidExclusionPatternThrowsException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid links exclusion pattern "(".');

        (new LinksDoctor($this->checker()))->audit($this->builder([]), ['exclude' => ['(']]);
    }

    public function testPageOptionFiltersAuditedPagesButBuildsTheWholeSite(): void
    {
        $post = $this->page('blog/post', '<a href="/">Home</a>')->setVariable('filepath', 'blog' . DIRECTORY_SEPARATOR . 'post.md');
        $virtual = $this->page('tags', '<a href="/missing/">Missing</a>')->setVirtual(true);
        $builder = $this->builder([$this->page('', '<a href="/missing/">Missing</a>'), $post, $virtual]);

        $report = (new LinksDoctor($this->checker()))->audit($builder, ['page' => 'blog/post.md']);

        self::assertSame(1, $report['summary']['pages_audited']);
        self::assertSame([], $report['findings']);
        self::assertSame(['dry-run' => true, 'page' => '', 'render-subset' => '', 'drafts' => false], $builder->buildOptions);

        $report = (new LinksDoctor($this->checker()))->audit($builder, ['include_virtual' => true]);
        self::assertSame(3, $report['summary']['pages_audited']);
        self::assertSame(['/', '/tags/'], array_column($report['findings'], 'page'));
    }

    /**
     * Returns a builder which skips the real build and provides already rendered pages.
     *
     * @param Page[] $pages
     */
    private function builder(array $pages, array $config = []): Builder
    {
        $config = array_replace_recursive([
            'baseurl' => 'https://example.com/',
            'assets' => ['remote' => ['useragent' => ['default' => 'Cecil test']]],
        ], $config);
        $builder = new class ($config, new NullLogger()) extends Builder {
            /** @var Page[] */
            public array $renderedPages = [];

            public array $buildOptions = [];

            public function build(array $options): Builder
            {
                $this->buildOptions = $options;
                $this->setPages(new PagesCollection('all-pages', $this->renderedPages));

                return $this;
            }
        };
        $builder->setSourceDir($this->sourceDir);
        $builder->renderedPages = $pages;

        return $builder;
    }

    private function page(string $path, string $body): Page
    {
        return (new Page($path === '' ? 'index' : $path))
            ->setPath($path)
            ->setVirtual(false)
            ->setVariable('published', true)
            ->addRendered(['html' => ['output' => "<!doctype html><html><head></head><body>$body</body></html>"]]);
    }

    /**
     * Returns a fake URL checker returning the given results (200 by default).
     *
     * @param array<string, array{status: int, errno?: int, error?: string, redirect?: string}> $results
     */
    private function checker(array $results = []): UrlCheckerInterface
    {
        return new class ($results) implements UrlCheckerInterface {
            /** @var array<int, string> */
            public array $checked = [];

            public array $options = [];

            public function __construct(private array $results)
            {
            }

            public function check(array $urls, array $options = []): array
            {
                array_push($this->checked, ...$urls);
                $this->options = $options;
                $checked = [];
                foreach ($urls as $url) {
                    $checked[$url] = ($this->results[$url] ?? []) + ['status' => 200, 'error' => '', 'errno' => 0, 'redirect' => ''];
                }

                return $checked;
            }
        };
    }
}

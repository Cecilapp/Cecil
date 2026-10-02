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
use Cecil\Doctor\SeoDoctor;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class SeoDoctorTest extends TestCase
{
    public function testWellOptimizedPageHasNoFindings(): void
    {
        $report = (new SeoDoctor())->audit($this->builder([$this->page('blog/post', $this->html())]));

        self::assertSame([
            'pages_audited' => 1,
            'pages_without_findings' => 1,
            'bad_count' => 0,
            'ok_count' => 0,
            'feedback_count' => 0,
        ], $report['summary']);
        self::assertSame([], $report['findings']);
    }

    public function testEmptyPageReportsAllMissingElements(): void
    {
        $report = (new SeoDoctor())->audit($this->builder([$this->page('index', '<html><body></body></html>')]));

        self::assertSame(1, $report['summary']['pages_audited']);
        self::assertSame(0, $report['summary']['pages_without_findings']);
        self::assertSame(3, $report['summary']['bad_count']);
        self::assertSame(0, $report['summary']['ok_count']);
        self::assertSame(6, $report['summary']['feedback_count']);
        self::assertSame([
            'Title tag' => 'bad',
            'Meta description' => 'bad',
            'Canonical URL' => 'feedback',
            'HTML lang attribute' => 'feedback',
            'Heading structure' => 'bad',
            'Open Graph title' => 'feedback',
            'Open Graph description' => 'feedback',
            'Open Graph image' => 'feedback',
            'Content length' => 'feedback',
        ], array_column($report['findings'], 'level', 'check'));
        // homepage label
        self::assertSame('/', $report['findings'][0]['page']);
        self::assertSame('No canonical link found in the rendered page.', $this->finding($report, 'Canonical URL')['details']);
        self::assertSame('Estimated body length: 0 words.', $this->finding($report, 'Content length')['details']);
    }

    public function testLengthHeadingsAndImagesIssuesAreReported(): void
    {
        $html = $this->html(
            title: 'Short',
            description: 'Too short description.',
            body: '<h1>One</h1><h1>Two</h1><img src="a.png"><img src="b.png" alt=" "><img src="c.png" alt="C">'
        );

        $report = (new SeoDoctor())->audit($this->builder([$this->page('blog/post', $html)]));

        self::assertSame(0, $report['summary']['bad_count']);
        self::assertSame(4, $report['summary']['ok_count']);
        self::assertSame('/blog/post/', $report['findings'][0]['page']);
        self::assertSame('5 characters. Recommended: 30-60.', $this->finding($report, 'Title length')['details']);
        self::assertSame('22 characters. Recommended: 120-160.', $this->finding($report, 'Meta description length')['details']);
        self::assertSame('Found 2 <h1> elements.', $this->finding($report, 'Heading structure')['details']);
        self::assertSame('2 image(s) are missing an alt attribute.', $this->finding($report, 'Images alt text')['details']);
    }

    public function testMissingCanonicalIsBadWhenCanonicalUrlsAreEnabled(): void
    {
        $html = str_replace('<link rel="canonical" href="https://example.com/blog/post/">', '', $this->html());

        $report = (new SeoDoctor())->audit($this->builder([$this->page('blog/post', $html)], ['canonicalurl' => true]));

        self::assertSame(
            ['page' => '/blog/post/', 'level' => 'bad', 'check' => 'Canonical URL', 'details' => 'Missing canonical link while canonical URLs are enabled.'],
            $report['findings'][0]
        );
    }

    public function testUnparsableHtmlIsBad(): void
    {
        $report = (new SeoDoctor())->audit($this->builder([$this->page('blog/post', '   ')]));

        self::assertSame(
            ['page' => '/blog/post/', 'level' => 'bad', 'check' => 'Rendered HTML', 'details' => 'The page could not be parsed as HTML.'],
            $report['findings'][0]
        );
    }

    public function testBodyTextFallsBackToRawHtmlWhenThereIsNoBody(): void
    {
        $config = ['doctor' => ['seo' => ['checks' => $this->onlyCheck('content_length'), 'content' => ['min_words' => 3]]]];

        $report = (new SeoDoctor())->audit($this->builder([$this->page('blog/post', '<title>one two three four</title>')], $config));

        self::assertSame(1, $report['summary']['pages_without_findings']);
    }

    public function testThresholdsAndChecksAreConfigurable(): void
    {
        $config = ['doctor' => ['seo' => [
            'title' => ['min' => 1, 'max' => 10],
            'description' => ['min' => 1, 'max' => 30],
            'content' => ['min_words' => 1],
            'checks' => [
                'canonical' => false,
                'h1' => false,
                'og_tags' => false,
                'img_alt' => false,
                'lang_attribute' => false,
            ],
        ]]];
        $html = '<html><head><title>Short</title><meta name="Description" content="Too short description."></head><body><p>Hello</p></body></html>';

        $report = (new SeoDoctor())->audit($this->builder([$this->page('blog/post', $html)], $config));

        self::assertSame(1, $report['summary']['pages_without_findings']);
        self::assertSame([], $report['findings']);
    }

    public function testPagesAreFilteredBeforeAudit(): void
    {
        $unpublished = $this->page('unpublished', $this->html())->setVariable('published', false);
        $notRendered = (new Page('not-rendered'))->setVirtual(false)->setVariable('published', true);
        $virtual = $this->page('virtual', $this->html())->setVirtual(true);
        $pages = [$this->page('blog/post', $this->html()), $unpublished, $notRendered, $virtual];

        $report = (new SeoDoctor())->audit($this->builder($pages));
        self::assertSame(1, $report['summary']['pages_audited']);

        $report = (new SeoDoctor())->audit($this->builder($pages), ['include_virtual' => true]);
        self::assertSame(2, $report['summary']['pages_audited']);
    }

    public function testBuildIsRunAsDryRunWithPageOption(): void
    {
        $builder = $this->builder([]);

        (new SeoDoctor())->audit($builder, ['page' => 'blog/post.md']);

        self::assertSame([
            'dry-run' => true,
            'page' => 'blog/post.md',
            'render-subset' => '',
            'drafts' => false,
        ], $builder->buildOptions);
    }

    /**
     * Returns a builder which skips the real build and provides already rendered pages.
     *
     * @param Page[] $pages
     */
    private function builder(array $pages, array $config = []): Builder
    {
        $builder = new class (['baseurl' => 'https://example.com/'] + $config, new NullLogger()) extends Builder {
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
        $builder->renderedPages = $pages;

        return $builder;
    }

    private function page(string $path, string $html): Page
    {
        return (new Page($path))
            ->setPath($path)
            ->setVirtual(false)
            ->setVariable('published', true)
            ->addRendered(['html' => ['output' => $html]]);
    }

    private function html(
        string $title = 'A well optimized page title for SEO',
        ?string $description = null,
        ?string $body = null
    ): string {
        $description ??= str_repeat('A meaningful description of the page content. ', 3);
        $body ??= '<h1>Heading</h1><img src="a.png" alt="An image"><p>' . str_repeat('word ', 300) . '</p>';

        return <<<HTML
            <!doctype html>
            <html lang="en">
            <head>
              <title>$title</title>
              <meta name="description" content="$description">
              <link rel="canonical" href="https://example.com/blog/post/">
              <meta property="og:title" content="$title">
              <meta property="og:description" content="$description">
              <meta property="og:image" content="https://example.com/image.png">
            </head>
            <body>$body</body>
            </html>
            HTML;
    }

    /**
     * @return array<string, bool>
     */
    private function onlyCheck(string $check): array
    {
        $checks = array_fill_keys(['title', 'description', 'canonical', 'h1', 'og_tags', 'img_alt', 'content_length', 'lang_attribute'], false);
        $checks[$check] = true;

        return $checks;
    }

    /**
     * @return array{page: string, level: string, check: string, details: string}
     */
    private function finding(array $report, string $check): array
    {
        foreach ($report['findings'] as $finding) {
            if ($finding['check'] === $check) {
                return $finding;
            }
        }

        self::fail(\sprintf('Finding "%s" not found.', $check));
    }
}

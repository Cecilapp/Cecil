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

namespace Cecil\Test\Unit\Generator;

use Cecil\Builder;
use Cecil\Collection\Page\Collection as PagesCollection;
use Cecil\Collection\Page\Page;
use Cecil\Generator\AbstractGenerator;
use Cecil\Generator\Pagination;
use Cecil\Generator\Section;
use Cecil\Url;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Pagination can be enabled globally (and disabled by a list page),
 * or disabled globally (and enabled by a list page).
 */
class PaginationTest extends TestCase
{
    private string $tmpDir;

    private Filesystem $filesystem;

    private Builder $builder;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cecil-pagination-test-' . uniqid('', true);
        $this->filesystem->mkdir($this->tmpDir);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->tmpDir);
    }

    public function testEnabledGloballyIsPaginated(): void
    {
        $this->build(['max' => 2]);

        self::assertSame(3, $this->builder->getPages()->get('blog')->getPaginator()['count']);
        self::assertTrue($this->builder->getPages()->has('blog/page/2'));
        self::assertTrue($this->builder->getPages()->has('blog/page/3'));
    }

    public function testEnabledGloballyAndDisabledByPageIsNotPaginated(): void
    {
        $this->build(['max' => 2], ['pagination' => ['enabled' => false]]);

        self::assertSame([], $this->builder->getPages()->get('blog')->getPaginator());
        self::assertFalse($this->builder->getPages()->has('blog/page/2'));
    }

    public function testEnabledGloballyAndDisabledByPageWithFalseIsNotPaginated(): void
    {
        $this->build(['max' => 2], ['pagination' => false]);

        self::assertSame([], $this->builder->getPages()->get('blog')->getPaginator());
        self::assertFalse($this->builder->getPages()->has('blog/page/2'));
    }

    public function testDisabledGloballyIsNotPaginated(): void
    {
        $this->build(['enabled' => false, 'max' => 2]);

        self::assertSame([], $this->builder->getPages()->get('blog')->getPaginator());
        self::assertFalse($this->builder->getPages()->has('blog/page/2'));
    }

    public function testDisabledGloballyWithFalseIsNotPaginated(): void
    {
        $this->build(false, ['pagination' => ['max' => 2]]);

        self::assertSame([], $this->builder->getPages()->get('blog')->getPaginator());
        self::assertFalse($this->builder->getPages()->has('blog/page/2'));
    }

    public function testDisabledGloballyAndEnabledByPageIsPaginated(): void
    {
        $this->build(['enabled' => false, 'max' => 2], ['pagination' => ['enabled' => true, 'max' => 4, 'path' => 'p']]);

        // page options are used
        $section = $this->builder->getPages()->get('blog');
        self::assertSame(2, $section->getPaginator()['count']);
        self::assertCount(4, $section->getPaginator()['pages']);
        self::assertTrue($this->builder->getPages()->has('blog/p/2'));
        self::assertFalse($this->builder->getPages()->has('blog/page/2'));
    }

    public function testDisabledGloballyAndEnabledByPageUsesConfigOptions(): void
    {
        $this->build(['enabled' => false, 'max' => 2], ['pagination' => ['enabled' => true]]);

        // configuration options are used
        self::assertSame(3, $this->builder->getPages()->get('blog')->getPaginator()['count']);
        self::assertTrue($this->builder->getPages()->has('blog/page/3'));
    }

    /**
     * Links of a page of the default language are IDs of the pages of this language.
     */
    public function testLinksOfDefaultLanguage(): void
    {
        $this->buildMultilingual();

        self::assertSame([
            'first' => 'blog',
            'self'  => 'blog',
            'next'  => 'blog/page/2',
            'last'  => 'blog/page/3',
            'path'  => 'blog/page',
        ], $this->builder->getPages()->get('blog')->getPaginator()['links']);
        $this->assertLinksAreUrlsOfPages('blog', 'en');
    }

    /**
     * Links of a page of another language are IDs of the pages (prefixed with the language),
     * resolved by `url()` to the URLs of the pages in the context of this language.
     */
    public function testLinksOfOtherLanguage(): void
    {
        $this->buildMultilingual();

        $links = $this->builder->getPages()->get('fr/blog/page/2')->getPaginator()['links'];
        self::assertSame([
            'first' => 'fr/blog',
            'prev'  => 'fr/blog',
            'self'  => 'fr/blog/page/2',
            'next'  => 'fr/blog/page/3',
            'last'  => 'fr/blog/page/3',
            'path'  => 'fr/blog/page',
        ], $links);
        self::assertSame('/fr/blog/', (string) new Url($this->builder, $links['first'], ['language' => 'fr']));
        self::assertSame('/fr/blog/page/3/', (string) new Url($this->builder, $links['next'], ['language' => 'fr']));
        $this->assertLinksAreUrlsOfPages('fr/blog', 'fr');
    }

    /**
     * Links of a page of another language are URLs of the pages, even with a custom path.
     */
    public function testLinksOfOtherLanguageWithCustomPath(): void
    {
        $this->buildMultilingual(['slug' => 'billets']);

        $next = $this->builder->getPages()->get('fr/blog')->getPaginator()['links']['next'];
        self::assertSame('/fr/blog/billets/page/2/', (string) new Url($this->builder, $next, ['language' => 'fr']));
        $this->assertLinksAreUrlsOfPages('fr/blog', 'fr');
    }

    /**
     * Builds a "blog" section of 5 posts in English (default language) and in French,
     * paginated by 2, then runs the Section and Pagination generators.
     *
     * @param array<string, mixed> $variables Front matter variables of the French section index page
     */
    private function buildMultilingual(array $variables = []): void
    {
        $this->builder = new Builder([
            'baseurl'   => 'https://example.com/',
            'language'  => 'en',
            'languages' => [
                ['code' => 'en', 'name' => 'English', 'locale' => 'en_US'],
                ['code' => 'fr', 'name' => 'Français', 'locale' => 'fr_FR'],
            ],
            'pages' => ['pagination' => ['max' => 2]],
        ], new NullLogger());
        $pages = [
            $this->page('blog', 'index.md'),
            $this->page('blog', 'index.fr.md', $variables, 'fr'),
        ];
        for ($i = 1; $i <= 5; $i++) {
            $pages[] = $this->page('blog', "post-$i.md", ['date' => "2024-01-0$i"]);
            $pages[] = $this->page('blog', "post-$i.fr.md", ['date' => "2024-01-0$i"], 'fr');
        }
        $this->builder->setPages(new PagesCollection('all-pages', $pages));

        $this->generate(new Section($this->builder));
        $this->generate(new Pagination($this->builder));
    }

    /**
     * Asserts that, in the context of the language, the URL of each link of each page
     * of the paginated list is the URL of the linked page itself (with a trailing slash).
     */
    private function assertLinksAreUrlsOfPages(string $listPageId, string $language): void
    {
        $count = $this->builder->getPages()->get($listPageId)->getPaginator()['count'];
        $pageIds = [1 => $listPageId];
        for ($i = 2; $i <= $count; $i++) {
            $pageIds[$i] = "$listPageId/page/$i";
        }
        $urlOf = fn (int $position): string => (string) new Url($this->builder, $this->builder->getPages()->get($pageIds[$position]));
        $urlOfLink = fn (string $link): string => (string) new Url($this->builder, $link, ['language' => $language]);
        foreach ($pageIds as $current => $pageId) {
            $links = $this->builder->getPages()->get($pageId)->getPaginator()['links'];
            self::assertStringEndsWith('/', $urlOf($current));
            self::assertSame($urlOf(1), $urlOfLink($links['first']), "first link of $pageId");
            self::assertSame($urlOf($current), $urlOfLink($links['self']), "self link of $pageId");
            self::assertSame($urlOf($count), $urlOfLink($links['last']), "last link of $pageId");
            if ($current > 1) {
                self::assertSame($urlOf($current - 1), $urlOfLink($links['prev']), "prev link of $pageId");
                self::assertSame($urlOf($current), $urlOfLink($links['path'] . '/' . $current), "path link of $pageId");
            }
            if ($current < $count) {
                self::assertSame($urlOf($current + 1), $urlOfLink($links['next']), "next link of $pageId");
            }
        }
    }

    /**
     * Builds a "blog" section of 5 posts, then runs the Section and Pagination generators.
     *
     * @param array<string, mixed>|bool $pagination Global pagination configuration
     * @param array<string, mixed>      $variables  Front matter variables of the section index page
     */
    private function build(array|bool $pagination, array $variables = []): void
    {
        $this->builder = new Builder(['pages' => ['pagination' => $pagination]], new NullLogger());
        $pages = [$this->page('blog', 'index.md', $variables)];
        for ($i = 1; $i <= 5; $i++) {
            $pages[] = $this->page('blog', "post-$i.md", ['date' => "2024-01-0$i"]);
        }
        $this->builder->setPages(new PagesCollection('all-pages', $pages));

        $this->generate(new Section($this->builder));
        $this->generate(new Pagination($this->builder));
    }

    /**
     * Runs a generator and merges its pages into the builder's collection (as the GeneratorManager does).
     */
    private function generate(AbstractGenerator $generator): void
    {
        foreach ($generator->runGenerate() as $page) {
            try {
                $this->builder->getPages()->add($page);
            } catch (\DomainException) {
                $this->builder->getPages()->replace($page->getId(), $page);
            }
        }
    }

    /**
     * @param array<string, mixed> $variables Front matter variables to apply
     * @param string|null          $language  Language of the page (default language if null)
     */
    private function page(string $relativePath, string $filename, array $variables = [], ?string $language = null): Page
    {
        $dir = $this->tmpDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        $this->filesystem->mkdir($dir);
        $filePath = $dir . DIRECTORY_SEPARATOR . $filename;
        file_put_contents($filePath, "---\ntitle: Test\n---\nBody");
        $file = new SplFileInfo($filePath, $relativePath, $relativePath . '/' . $filename);

        $page = (new Page($file))->parse()->setVariables($variables);
        $page->setVariable('language', $language ?? $this->builder->getConfig()->getLanguageDefault());

        return $page;
    }
}

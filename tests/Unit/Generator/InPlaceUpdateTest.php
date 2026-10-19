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
use Cecil\Generator\Homepage;
use Cecil\Generator\Pagination;
use Cecil\Generator\Section;
use Cecil\Generator\Taxonomy;
use Cecil\Step\Taxonomies\Create as CreateTaxonomies;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Generators update existing pages in place (instead of cloning them),
 * so that references held by other objects remain the instances that will be rendered.
 */
class InPlaceUpdateTest extends TestCase
{
    private string $tmpDir;

    private Filesystem $filesystem;

    private Builder $builder;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cecil-in-place-test-' . uniqid('', true);
        $this->filesystem->mkdir($this->tmpDir);
        $this->builder = new Builder([
            'taxonomies' => ['tags' => 'tag'],
            'pages' => ['pagination' => ['max' => 2]],
        ], new NullLogger());
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->tmpDir);
    }

    public function testHomepageIsUpdatedInPlace(): void
    {
        $index = $this->page('', 'index.md');
        $this->builder->setPages(new PagesCollection('all-pages', [
            $index,
            $this->page('', 'about.md'),
        ]));

        $this->generate(new Homepage($this->builder));

        self::assertSame($index, $this->builder->getPages()->get('index'));
        self::assertSame('homepage', $index->getType());
    }

    public function testTermListsTheSectionIndexPageThatWillBeRendered(): void
    {
        // a section index page with a term
        $this->builder->setPages(new PagesCollection('all-pages', [
            $this->page('blog', 'index.md', ['tags' => ['foo']]),
            $this->page('blog', 'post.md', ['tags' => ['foo']]),
        ]));
        // terms are collected before generators run
        (new CreateTaxonomies($this->builder))->process();

        $this->generate(new Section($this->builder));
        $this->generate(new Taxonomy($this->builder));

        $section = $this->builder->getPages()->get('blog');
        $termPages = $this->builder->getPages()->get('tags/foo')->getPages();
        self::assertSame($section, $termPages->get('blog'));
        self::assertSame('section', $termPages->get('blog')->getType());
    }

    public function testTermPageFromContentIsUpdatedInPlace(): void
    {
        $term = $this->page('tags', 'foo.md', ['title' => 'Foo']);
        $this->builder->setPages(new PagesCollection('all-pages', [
            $term,
            $this->page('blog', 'post.md', ['tags' => ['foo']]),
        ]));
        (new CreateTaxonomies($this->builder))->process();

        $this->generate(new Taxonomy($this->builder));

        self::assertSame($term, $this->builder->getPages()->get('tags/foo'));
        self::assertSame('term', $term->getType());
        self::assertSame('Foo', $term->getVariable('title'));
    }

    public function testFirstPaginatedPageIsTheListPage(): void
    {
        $this->builder->setPages(new PagesCollection('all-pages', [
            $this->page('blog', 'index.md'),
            $this->page('blog', 'post-1.md', ['date' => '2024-01-01']),
            $this->page('blog', 'post-2.md', ['date' => '2024-01-02']),
            $this->page('blog', 'post-3.md', ['date' => '2024-01-03']),
        ]));

        $this->generate(new Section($this->builder));
        $section = $this->builder->getPages()->get('blog');
        $this->generate(new Pagination($this->builder));

        // the first page is the section page referenced by its pages...
        $post = $this->builder->getPages()->get('blog/post-1');
        self::assertSame($section, $this->builder->getPages()->get('blog'));
        self::assertSame($section, $post->getParent());
        self::assertSame(1, $section->getPaginator()['current']);
        // ... and others pages are distinct instances
        $page2 = $this->builder->getPages()->get('blog/page/2');
        self::assertNotSame($section, $page2);
        self::assertSame(2, $page2->getPaginator()['current']);
        self::assertTrue($page2->getVariable('paginated'));
        self::assertNull($page2->getVariable('alias'));
        self::assertNull($page2->getVariable('menu'));
        self::assertNull($section->getVariable('paginated'));
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
     */
    private function page(string $relativePath, string $filename, array $variables = []): Page
    {
        $dir = $this->tmpDir . ($relativePath !== '' ? DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath) : '');
        $this->filesystem->mkdir($dir);
        $filePath = $dir . DIRECTORY_SEPARATOR . $filename;
        file_put_contents($filePath, "---\ntitle: Test\n---\nBody");
        $file = new SplFileInfo($filePath, $relativePath, ($relativePath !== '' ? $relativePath . '/' : '') . $filename);

        $page = (new Page($file))->parse()->setVariables($variables);
        // the language is set before generators run (default language if no suffix)
        if ($page->getVariable('language') === null) {
            $page->setVariable('language', $this->builder->getConfig()->getLanguageDefault());
        }

        return $page;
    }
}

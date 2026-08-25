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
use Cecil\Generator\Homepage;
use Cecil\Generator\Section;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\SplFileInfo;

class SectionTest extends TestCase
{
    private string $tmpDir;

    private Filesystem $filesystem;

    private Builder $builder;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cecil-section-test-' . uniqid('', true);
        $this->filesystem->mkdir($this->tmpDir);
        $this->builder = new Builder();
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->tmpDir);
    }

    public function testSubSectionIsCreatedFromNestedIndex(): void
    {
        $this->builder->setPages(new PagesCollection('all-pages', [
            $this->page('blog', 'index.md'),
            $this->page('blog', 'post.md'),
            $this->page('blog/2024', 'index.md'),
            $this->page('blog/2024', 'deep-post.md'),
        ]));

        $generated = (new Section($this->builder))->runGenerate();

        // both the section and the sub-section pages are generated
        self::assertTrue($generated->has('blog'));
        self::assertTrue($generated->has('blog/2024'));
        self::assertSame('section', $generated->get('blog')->getType());
        self::assertSame('section', $generated->get('blog/2024')->getType());
        self::assertSame('blog/2024', $generated->get('blog/2024')->getSection());
    }

    public function testSubSectionPageBelongsToBothSections(): void
    {
        $this->builder->setPages(new PagesCollection('all-pages', [
            $this->page('blog', 'index.md'),
            $this->page('blog', 'post.md'),
            $this->page('blog/2024', 'index.md'),
            $this->page('blog/2024', 'deep-post.md'),
        ]));

        $generated = (new Section($this->builder))->runGenerate();

        $section = $this->pagesIds($generated->get('blog')->getPages());
        $subSection = $this->pagesIds($generated->get('blog/2024')->getPages());

        // the sub-section content page belongs to both the section and the sub-section
        self::assertContains('blog/2024/deep-post', $section);
        self::assertContains('blog/post', $section);
        self::assertContains('blog/2024/deep-post', $subSection);
    }

    public function testSubSectionIndexIsNotListedInParent(): void
    {
        $this->builder->setPages(new PagesCollection('all-pages', [
            $this->page('blog', 'index.md'),
            $this->page('blog/2024', 'index.md'),
            $this->page('blog/2024', 'deep-post.md'),
        ]));

        $generated = (new Section($this->builder))->runGenerate();

        // the sub-section index page is not listed as a child of its parent section
        self::assertNotContains('blog/2024', $this->pagesIds($generated->get('blog')->getPages()));
    }

    public function testSubSectionWithOnlyIndexIsGenerated(): void
    {
        $this->builder->setPages(new PagesCollection('all-pages', [
            $this->page('blog', 'index.md'),
            $this->page('blog', 'post.md'),
            $this->page('blog/2024', 'index.md'),
        ]));

        $generated = (new Section($this->builder))->runGenerate();

        // a sub-section containing only an "index.md" is still generated...
        self::assertTrue($generated->has('blog/2024'));
        self::assertSame('section', $generated->get('blog/2024')->getType());
        // ...and its index page is not listed in its parent section
        self::assertNotContains('blog/2024', $this->pagesIds($generated->get('blog')->getPages()));
    }

    public function testNestedFolderWithoutIndexIsNotASubSection(): void
    {
        $this->builder->setPages(new PagesCollection('all-pages', [
            $this->page('blog', 'index.md'),
            $this->page('blog/2024', 'deep-post.md'),
        ]));

        $generated = (new Section($this->builder))->runGenerate();

        // no "index.md" in "blog/2024": no sub-section is created
        self::assertFalse($generated->has('blog/2024'));
        self::assertContains('blog/2024/deep-post', $this->pagesIds($generated->get('blog')->getPages()));
    }

    public function testPageParentIsItsSection(): void
    {
        $this->builder->setPages(new PagesCollection('all-pages', [
            $this->page('blog', 'index.md'),
            $this->page('blog', 'post.md'),
        ]));

        (new Section($this->builder))->runGenerate();

        $post = $this->builder->getPages()->get('blog/post');
        self::assertInstanceOf(Page::class, $post->getParent());
        self::assertSame('blog', $post->getParent()->getId());
    }

    public function testPageParentIsItsDeepestSubSection(): void
    {
        $this->builder->setPages(new PagesCollection('all-pages', [
            $this->page('blog', 'index.md'),
            $this->page('blog/2024', 'index.md'),
            $this->page('blog/2024', 'deep-post.md'),
        ]));

        (new Section($this->builder))->runGenerate();

        // a sub-section page's parent is its most specific (deepest) section
        $deepPost = $this->builder->getPages()->get('blog/2024/deep-post');
        self::assertSame('blog/2024', $deepPost->getParent()->getId());
    }

    public function testSubSectionParentIsItsParentSection(): void
    {
        $this->builder->setPages(new PagesCollection('all-pages', [
            $this->page('blog', 'index.md'),
            $this->page('blog', 'post.md'),
            $this->page('blog/2024', 'index.md'),
            $this->page('blog/2024', 'deep-post.md'),
        ]));

        $generated = (new Section($this->builder))->runGenerate();

        // the sub-section's parent is its parent section
        self::assertSame('blog', $generated->get('blog/2024')->getParent()->getId());
    }

    public function testTopSectionHasNoParent(): void
    {
        $this->builder->setPages(new PagesCollection('all-pages', [
            $this->page('blog', 'index.md'),
            $this->page('blog', 'post.md'),
        ]));

        $generated = (new Section($this->builder))->runGenerate();

        self::assertNull($generated->get('blog')->getParent());
    }

    public function testPageAncestorsAreItsSectionsFromNearestToFarthest(): void
    {
        $this->builder->setPages(new PagesCollection('all-pages', [
            $this->page('blog', 'index.md'),
            $this->page('blog/2024', 'index.md'),
            $this->page('blog/2024/06', 'index.md'),
            $this->page('blog/2024/06', 'deep-post.md'),
        ]));

        (new Section($this->builder))->runGenerate();

        $deepPost = $this->builder->getPages()->get('blog/2024/06/deep-post');
        $ancestors = $this->pagesIds($deepPost->getAncestors());

        // from the nearest to the farthest section
        self::assertSame(['blog/2024/06', 'blog/2024', 'blog'], $ancestors);
    }

    public function testTopSectionHasNoAncestors(): void
    {
        $this->builder->setPages(new PagesCollection('all-pages', [
            $this->page('blog', 'index.md'),
            $this->page('blog', 'post.md'),
        ]));

        $generated = (new Section($this->builder))->runGenerate();

        self::assertCount(0, $generated->get('blog')->getAncestors());
    }

    public function testTopLevelSectionIsFlaggedAndAddedToMainMenu(): void
    {
        $this->builder->setPages(new PagesCollection('all-pages', [
            $this->page('blog', 'index.md'),
            $this->page('blog', 'post.md'),
        ]));

        $generated = (new Section($this->builder))->runGenerate();

        $blog = $generated->get('blog');
        self::assertTrue($blog->getVariable('toplevel'));
        self::assertArrayHasKey('main', (array) $blog->getVariable('menu'));
    }

    public function testSubSectionIsNotTopLevelAndNotInMainMenu(): void
    {
        $this->builder->setPages(new PagesCollection('all-pages', [
            $this->page('blog', 'index.md'),
            $this->page('blog/2024', 'index.md'),
            $this->page('blog/2024', 'deep-post.md'),
        ]));

        $generated = (new Section($this->builder))->runGenerate();

        $subSection = $generated->get('blog/2024');
        self::assertFalse($subSection->getVariable('toplevel'));
        self::assertNull($subSection->getVariable('menu'));
    }

    public function testSectionImmediateChildSections(): void
    {
        $this->builder->setPages(new PagesCollection('all-pages', [
            $this->page('blog', 'index.md'),
            $this->page('blog/2024', 'index.md'),
            $this->page('blog/2024', 'post.md'),
            $this->page('blog/2024/06', 'index.md'),
            $this->page('blog/2024/06', 'deep.md'),
        ]));

        $generated = (new Section($this->builder))->runGenerate();

        // only immediate descendant sections are returned
        self::assertSame(['blog/2024'], $this->pagesIds($generated->get('blog')->getSections()));
        self::assertSame(['blog/2024/06'], $this->pagesIds($generated->get('blog/2024')->getSections()));
        self::assertCount(0, $generated->get('blog/2024/06')->getSections());
    }

    public function testHomepageImmediateChildSectionsAreTopLevel(): void
    {
        $this->builder->setPages(new PagesCollection('all-pages', [
            $this->page('blog', 'index.md'),
            $this->page('blog', 'post.md'),
            $this->page('projects', 'index.md'),
            $this->page('projects', 'a.md'),
            $this->page('blog/2024', 'index.md'),
            $this->page('blog/2024', 'deep.md'),
        ]));

        // runs the Section generator and merges its pages into the collection
        foreach ((new Section($this->builder))->runGenerate() as $page) {
            try {
                $this->builder->getPages()->add($page);
            } catch (\DomainException) {
                $this->builder->getPages()->replace($page->getId(), $page);
            }
        }

        $home = (new Homepage($this->builder))->runGenerate()->get('index');
        $homeSections = $this->pagesIds($home->getSections());
        sort($homeSections);

        self::assertSame(['blog', 'projects'], $homeSections);
    }

    /**
     * @return string[]
     */
    private function pagesIds(PagesCollection $pages): array
    {
        return array_map(fn (Page $page): string => $page->getId(), $pages->toArray());
    }

    private function page(string $relativePath, string $filename): Page
    {
        $dir = $this->tmpDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        $this->filesystem->mkdir($dir);
        $filePath = $dir . DIRECTORY_SEPARATOR . $filename;
        file_put_contents($filePath, "---\ntitle: Test\n---\nBody");
        $file = new SplFileInfo($filePath, $relativePath, $relativePath . '/' . $filename);

        return (new Page($file))->parse();
    }
}

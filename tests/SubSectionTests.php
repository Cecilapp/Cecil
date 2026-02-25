<?php

/**
 * This file is part of Cecil.
 *
 * (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Test;

use Cecil\Builder;
use Cecil\Collection\Page\Page;
use Cecil\Collection\Page\Type;
use Cecil\Config;
use Cecil\Logger\PrintLogger;
use Cecil\Util;
use Symfony\Component\Filesystem\Filesystem;

class SubSectionTests extends \PHPUnit\Framework\TestCase
{
    protected static $source;
    protected static $config;
    protected static $destination;
    protected static $builder;

    public static function setUpBeforeClass(): void
    {
        self::$source = Util::joinFile(__DIR__, 'fixtures/website');
        self::$config = Util::joinFile(self::$source, 'config.yml');
        self::$destination = self::$source;

        putenv('CECIL_DEBUG=true');
        self::$builder = Builder::create(Config::loadFile(self::$config), new PrintLogger(Builder::VERBOSITY_DEBUG))
            ->setSourceDir(self::$source)
            ->setDestinationDir(self::$destination);
        self::$builder->build([
            'drafts'  => true,
            'dry-run' => true,
        ]);
    }

    public static function tearDownAfterClass(): void
    {
        $fs = new Filesystem();
        $fs->remove(Util::joinFile(self::$destination, '.cecil'));
        $fs->remove(Util::joinFile(self::$destination, '.cache'));
        $fs->remove(Util::joinFile(self::$destination, '_site'));
    }

    protected function getBuilder(): Builder
    {
        return self::$builder;
    }

    /**
     * Test that sub-section pages are created.
     */
    public function testSubSectionPagesExist()
    {
        $builder = $this->getBuilder();
        $pages = $builder->getPages();

        // The "blog/tutorials" sub-section should exist as a Section-type page.
        $this->assertTrue($pages->has('blog/tutorials'), 'Sub-section "blog/tutorials" should exist');
        $tutorialsPage = $pages->get('blog/tutorials');
        $this->assertEquals(Type::SECTION->value, $tutorialsPage->getType(), '"blog/tutorials" should be a section');

        // The "blog/tutorials/advanced" sub-section should also exist.
        $this->assertTrue($pages->has('blog/tutorials/advanced'), 'Sub-section "blog/tutorials/advanced" should exist');
        $advancedPage = $pages->get('blog/tutorials/advanced');
        $this->assertEquals(Type::SECTION->value, $advancedPage->getType(), '"blog/tutorials/advanced" should be a section');
    }

    /**
     * Test parent/child relationships.
     */
    public function testParentChildRelationships()
    {
        $builder = $this->getBuilder();
        $pages = $builder->getPages();

        $blogPage = $pages->get('blog');
        $tutorialsPage = $pages->get('blog/tutorials');
        $advancedPage = $pages->get('blog/tutorials/advanced');

        // blog should have sub-sections
        $this->assertTrue($blogPage->hasSubSections(), '"blog" should have sub-sections');

        // tutorials should be a sub-section of blog
        $this->assertTrue($tutorialsPage->isSubSection(), '"blog/tutorials" should be a sub-section');
        $this->assertTrue($tutorialsPage->hasParentSection(), '"blog/tutorials" should have a parent section');
        $this->assertEquals('blog', $tutorialsPage->getParentSection()->getId(), 'Parent of "blog/tutorials" should be "blog"');

        // advanced should be a sub-section of tutorials
        $this->assertTrue($advancedPage->isSubSection(), '"blog/tutorials/advanced" should be a sub-section');
        $this->assertEquals('blog/tutorials', $advancedPage->getParentSection()->getId());

        // blog should NOT be a sub-section
        $this->assertFalse($blogPage->isSubSection(), '"blog" should NOT be a sub-section');
    }

    /**
     * Test that pages are assigned to the correct (deepest) section.
     */
    public function testPagesAssignedToDeepestSection()
    {
        $builder = $this->getBuilder();
        $pages = $builder->getPages();

        $tutorialsPage = $pages->get('blog/tutorials');
        $advancedPage = $pages->get('blog/tutorials/advanced');

        // Tutorials section should contain its direct pages
        $tutorialPages = $tutorialsPage->getPages();
        $this->assertNotNull($tutorialPages, 'Tutorials section should have pages');
        $this->assertGreaterThanOrEqual(2, \count($tutorialPages), 'Tutorials section should have at least 2 pages');

        // Advanced section should contain its direct pages
        $advancedPages = $advancedPage->getPages();
        $this->assertNotNull($advancedPages, 'Advanced section should have pages');
        $this->assertGreaterThanOrEqual(1, \count($advancedPages), 'Advanced section should have at least 1 page');
    }

    /**
     * Test section depth calculation.
     */
    public function testSectionDepth()
    {
        $builder = $this->getBuilder();
        $pages = $builder->getPages();

        $blogPage = $pages->get('blog');
        $tutorialsPage = $pages->get('blog/tutorials');
        $advancedPage = $pages->get('blog/tutorials/advanced');

        $this->assertEquals(0, $blogPage->getSectionDepth(), '"blog" depth should be 0');
        $this->assertEquals(1, $tutorialsPage->getSectionDepth(), '"blog/tutorials" depth should be 1');
        $this->assertEquals(2, $advancedPage->getSectionDepth(), '"blog/tutorials/advanced" depth should be 2');
    }

    /**
     * Test section breadcrumb.
     */
    public function testSectionBreadcrumb()
    {
        $builder = $this->getBuilder();
        $pages = $builder->getPages();

        $advancedPage = $pages->get('blog/tutorials/advanced');
        $breadcrumb = $advancedPage->getSectionBreadcrumb();

        $this->assertCount(3, $breadcrumb, 'Breadcrumb for "blog/tutorials/advanced" should have 3 items');
        $this->assertEquals('blog', $breadcrumb[0]->getId());
        $this->assertEquals('blog/tutorials', $breadcrumb[1]->getId());
        $this->assertEquals('blog/tutorials/advanced', $breadcrumb[2]->getId());
    }

    /**
     * Test getAllPagesRecursive.
     */
    public function testGetAllPagesRecursive()
    {
        $builder = $this->getBuilder();
        $pages = $builder->getPages();

        $tutorialsPage = $pages->get('blog/tutorials');
        $allPages = $tutorialsPage->getAllPagesRecursive();

        // Should include direct pages (2 tutorials) + advanced pages (1 tutorial)
        $this->assertGreaterThanOrEqual(3, \count($allPages), 'Recursive pages should include sub-section pages');
    }

    /**
     * Test that root section no longer contains sub-section pages.
     */
    public function testRootSectionExcludesSubSectionPages()
    {
        $builder = $this->getBuilder();
        $pages = $builder->getPages();

        $blogPage = $pages->get('blog');
        $blogDirectPages = $blogPage->getPages();

        // Blog section's direct pages should NOT include tutorial pages
        if ($blogDirectPages !== null) {
            foreach ($blogDirectPages as $page) {
                // Check page section is "blog", not a sub-section
                $this->assertEquals(
                    'blog',
                    $page->getSection(),
                    \sprintf('Blog direct page "%s" should have section "blog"', $page->getId())
                );
            }
        }
    }

    /**
     * Test that non-sub-section folders without index.md are not treated as sub-sections.
     * (backward compatibility)
     */
    public function testFoldersWithoutIndexAreNotSubSections()
    {
        $builder = $this->getBuilder();
        $pages = $builder->getPages();

        // The "assets" folder under Blog should NOT create a sub-section
        // (there's no blog/assets/index.md)
        $this->assertFalse($pages->has('blog/assets'), 'Folders without index.md should NOT become sub-sections');
    }
}

<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Test;

use Cecil\Builder;
use Cecil\Collection\Collection as PagesCollection;
use Cecil\Collection\Page\Page;
use Cecil\Converter\Converter;
use Cecil\Step\PagesConvert;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class UnitTests extends \PHPUnit\Framework\TestCase
{
    protected $sourceDir;
    protected $destDir;
    protected $iterator;
    protected $file;

    public function setUp()
    {
        $this->sourceDir = (__DIR__.'/fixtures/website');
        $this->destDir = $this->sourceDir;
        $this->iterator = $this->createContentIterator();
        $this->file = $this->createFile();
    }

    public function tearDown()
    {
        $fs = new Filesystem();
        $fs->remove($this->destDir.'/_site');
    }

    public function createContentIterator()
    {
        return Finder::create()
            ->files()
            ->in(__DIR__.'/fixtures/content')
            ->name('*.md');
    }

    public function createFile()
    {
        return new SplFileInfo(
            __DIR__.'/fixtures/content/Section1/Page1.md',
            'Section1',
            'Section1/Page1.md'
        );
    }

    public function testCreate()
    {
        $this->assertInstanceOf('Cecil\Builder', Builder::create());
    }

    public function testOptions()
    {
        $options = [
            'option1' => 'Option 1',
            'option2' => 'Option 2',
        ];
        $builder = (new Builder())->setConfig($options);
        //$this->assertEquals($options, $builder->getOptions()->getAsArray());
        $this->assertArraySubset($options, $builder->getConfig()->getAsArray());
    }

    public function testContentIterator()
    {
        $iterator = $this->iterator;
        $this->assertInstanceOf('Symfony\Component\Finder\Finder', $iterator);
        $this->assertCount(1, $iterator);
        $this->assertContainsOnlyInstancesOf('Symfony\Component\Finder\SplFileInfo', $iterator);
    }

    public function testParsePage()
    {
        $preParsedPath = __DIR__.'/fixtures/content_parsed/Page1.md';
        $parsed = (new Page($this->file))->parse();
        $this->assertStringEqualsFile(sprintf("$preParsedPath/%s", 'frontmatter.yaml'), $parsed->getFrontmatter());
        $this->assertStringEqualsFile(sprintf("$preParsedPath/%s", 'body.md'), $parsed->getBody());
    }

    public function testAddPageToCollection()
    {
        $page = new Page($this->file);
        $pagesCollection = new PagesCollection();
        $addResult = $pagesCollection->add($page);
        $this->assertArrayHasKey('section1/page1', $addResult);
    }

    public function testConvertYaml()
    {
        $page = new Page($this->file);
        $page->parse();
        $variables = (new Converter())
            ->convertFrontmatter(
                $page->getFrontmatter(),
                'yaml'
            );
        $this->assertArrayHasKey('title', $variables);
        $this->assertArrayHasKey('date', $variables);
    }

    public function testConvertMarkdown()
    {
        $page = new Page($this->file);
        $page->parse();
        $html = (new Converter())
            ->convertBody($page->getBody());
        $this->assertSame('<p>Content of page 1.</p>', $html);
    }

    public function testConvertPage()
    {
        $pagesCollection = new PagesCollection('collection-testconvertpage');

        /* @var $page Page */
        $page = new Page($this->file);
        $page->parse();
        $pagesCollection->add($page);

        $page = (new PagesConvert(Builder::create()))
            ->convertPage($page, 'yaml');

        $pagesCollection->replace($page->getId(), $page);
        unset($page);

        $page = $pagesCollection->get('section1/page1');
        $this->assertObjectHasAttribute('html', $page);
        $this->assertObjectHasAttribute('properties', $page);
        $this->assertSame('Page 1', $page->getVariable('title'));
        $this->assertSame('<p>Content of page 1.</p>', $page->getBodyHtml());
        //$this->assertEquals(1427839200, $page['date'], '', 5);}
    }

    public function testAddPage()
    {
        $page = new Page('testAddPage');
        $pagesCollection = new PagesCollection('testAddPage');

        $page->setId('id-of-page')
            ->setVariable('title', 'title-of-page');
        $pagesCollection->add($page);

        $this->assertContains($page, $pagesCollection);
    }

    public function testGetPage()
    {
        $page = new Page('testGetPage');
        $pagesCollection = new PagesCollection('testGetPage');

        $page->setId('id-of-page')
            ->setVariable('title', 'title-of-page');
        $pagesCollection->add($page);

        $this->assertNotNull($pagesCollection->get('id-of-page'));
    }
}

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

namespace Cecil\Test\Unit\Collection\Page;

use Cecil\Collection\Page\Collection;
use Cecil\Collection\Page\Page;
use Cecil\Collection\Taxonomy\Term;
use Cecil\Collection\Taxonomy\Vocabulary;
use Cecil\Exception\RuntimeException;
use Cecil\Util;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\SplFileInfo;

class PageTest extends TestCase
{
    private string $tmpDir;

    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cecil-page-test-' . uniqid('', true);
        $this->filesystem->mkdir($this->tmpDir);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->tmpDir);
    }

    public function testPageCreationWithStringId(): void
    {
        $page = new Page('test-page');
        self::assertSame('test-page', $page->getId());
    }

    public function testPageToStringReturnsId(): void
    {
        $page = new Page('my/page');
        self::assertSame('my/page', (string) $page);
    }

    public function testSlugifyDelegatesToSlugifier(): void
    {
        $input = 'Hello World';
        self::assertSame(Util\Slugifier::slugify($input), Util\Slugifier::slugify($input));
    }

    public function testSlugifyPatternConstantMatchesSlugifier(): void
    {
        self::assertSame(Util\Slugifier::SLUGIFY_PATTERN, Page::SLUGIFY_PATTERN);
    }

    public function testDefaultTypeIsPage(): void
    {
        $page = new Page('test');
        self::assertSame('page', $page->getType());
    }

    public function testVirtualByDefault(): void
    {
        $page = new Page('test');
        self::assertTrue($page->isVirtual());
    }

    public function testSetAndGetVariable(): void
    {
        $page = new Page('test');
        $page->setVariable('title', 'My Title');
        self::assertSame('My Title', $page->getVariable('title'));
    }

    public function testSetAndGetPath(): void
    {
        $page = new Page('test');
        $page->setPath('section/test');
        self::assertSame('section/test', $page->getPath());
    }

    public function testGetIdWithoutLangStripsLanguagePrefixWhenPresent(): void
    {
        $page = new Page('fr/section/page');
        $page->setVariable('language', 'fr');

        self::assertSame('section/page', $page->getIdWithoutLang());
    }

    public function testGetIdWithoutLangKeepsOriginalWhenLanguageIsNotSet(): void
    {
        $page = new Page('section/page');

        self::assertSame('section/page', $page->getIdWithoutLang());
    }

    public function testSetPathHandlesHomepageAndSectionIndex(): void
    {
        $homepage = new Page('home');
        $homepage->setPath('index');
        self::assertSame('', $homepage->getPath());

        $section = new Page('section-index');
        $section->setPath('blog/index');
        self::assertSame('blog', $section->getPath());
    }

    public function testSetVariableDraftTrueSetsPublishedFalse(): void
    {
        $page = new Page('draft');
        $page->setVariable('draft', true);

        self::assertFalse($page->getVariable('published'));
    }

    public function testSetVariableScheduleCanPublishPage(): void
    {
        $page = new Page('scheduled');
        $page->setVariable('schedule', [
            'publish' => '2000-01-01',
        ]);
        self::assertTrue($page->getVariable('published'));

        $page->setVariable('schedule', [
            'expiry' => '2999-01-01',
        ]);
        self::assertTrue($page->getVariable('published'));
    }

    public function testSetVariablePathRejectsNonSlugifiedValue(): void
    {
        $page = new Page('invalid-slug');

        $this->expectException(\Cecil\Exception\RuntimeException::class);
        $this->expectExceptionMessage('variable should be');

        $page->setVariable('path', 'Bad Path');
    }

    public function testRenderedAndPaginatorHelpers(): void
    {
        $page = new Page('helpers');
        $page->setBodyHtml('<p>Hello</p>');
        $page->addRendered(['html' => '<p>Hello</p>']);
        $page->setPaginator(['page' => 1]);

        self::assertSame('<p>Hello</p>', $page->getBodyHtml());
        self::assertSame('<p>Hello</p>', $page->getContent());
        self::assertSame(['html' => '<p>Hello</p>'], $page->getRendered());
        self::assertSame(['page' => 1], $page->getPaginator());
        self::assertSame(['page' => 1], $page->getPagination());
    }

    public function testFrontmatterVariablesCanBeSetAndUnset(): void
    {
        $page = new Page('frontmatter');
        $page->setVariable('custom', 'value');
        self::assertTrue($page->hasVariable('custom'));

        $page->unVariable('custom');
        self::assertFalse($page->hasVariable('custom'));

        $page->setFmVariables(['title' => 'Front']);
        self::assertSame(['title' => 'Front'], $page->getFmVariables());
    }

    public function testPageCreationWithInvalidIdThrowsRuntimeException(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Create a page with a string ID or a SplFileInfo.');

        new Page(123);
    }

    public function testPageCreationFromSplFileInfoSetsExpectedMetadata(): void
    {
        $file = $this->createFileInfo('blog', '01-hello.fr.md', "---\ntitle: Hello\n---\nBody");
        $page = new Page($file);

        self::assertFalse($page->isVirtual());
        self::assertSame('fr/blog/hello', $page->getId());
        self::assertSame('blog/hello', $page->getPath());
        self::assertSame('hello', $page->getSlug());
        self::assertSame('blog', $page->getFolder());
        self::assertSame(1, $page->getVariable('weight'));
        self::assertSame('fr', $page->getVariable('language'));
        self::assertSame('blog/hello', $page->getVariable('langref'));
        self::assertSame('hello', $page->getVariable('title'));
        self::assertSame('01-hello.fr.md', $page->getFileName());
        self::assertIsString($page->getFilePath());
    }

    public function testReadmeAtRootCreatesHomepageType(): void
    {
        $file = $this->createFileInfo('', 'README.md', 'Hello');
        $page = new Page($file);

        self::assertSame('homepage', $page->getType());
        self::assertSame('index', $page->getId());
    }

    public function testLocalizedReadmeAtRootCreatesHomepageType(): void
    {
        $file = $this->createFileInfo('', 'README.fr.md', 'Bonjour');
        $page = new Page($file);

        self::assertSame('homepage', $page->getType());
        self::assertSame('fr/index', $page->getId());
        self::assertSame('fr', $page->getVariable('language'));
    }

    public function testLocalizedReadmeInSectionIsSectionIndex(): void
    {
        $file = $this->createFileInfo('blog', 'README.fr.md', 'Bonjour');
        $page = new Page($file);

        self::assertTrue($page->isSectionIndex());
        self::assertSame('fr/blog', $page->getId());
        self::assertSame('blog', $page->getPath());
        self::assertSame('fr', $page->getVariable('language'));
    }

    public function testIsSectionIndexTrueForFolderIndex(): void
    {
        $file = $this->createFileInfo('blog', 'index.md', 'Hello');
        $page = new Page($file);

        self::assertTrue($page->isSectionIndex());
    }

    public function testIsSectionIndexTrueForNestedFolderIndex(): void
    {
        $file = $this->createFileInfo('blog/2024', 'index.md', 'Hello');
        $page = new Page($file);

        self::assertTrue($page->isSectionIndex());
        self::assertSame('blog/2024', $page->getPath());
    }

    public function testIsSectionIndexFalseForRegularPage(): void
    {
        $file = $this->createFileInfo('blog', 'post.md', 'Hello');
        $page = new Page($file);

        self::assertFalse($page->isSectionIndex());
    }

    public function testIsSectionIndexFalseForHomepage(): void
    {
        $file = $this->createFileInfo('', 'index.md', 'Hello');
        $page = new Page($file);

        self::assertFalse($page->isSectionIndex());
    }

    public function testGetPathnameIsAliasOfGetPath(): void
    {
        $page = new Page('alias');
        $page->setPath('section/alias');

        self::assertSame($page->getPath(), $page->getPathname());
    }

    public function testSetSectionAndUnSection(): void
    {
        $page = new Page('section');

        $page->setSection('blog');
        self::assertSame('blog', $page->getSection());

        $page->unSection();
        self::assertNull($page->getSection());
    }

    public function testSetAndGetPagesCollection(): void
    {
        $page = new Page('parent');
        $pages = new Collection('pages', [new Page('child')]);

        $page->setPages($pages);

        self::assertSame($pages, $page->getPages());
    }

    public function testSetAndGetTermsVocabulary(): void
    {
        $page = new Page('taxonomy-page');
        $terms = new Vocabulary('tags');
        $terms->add((new Term('php'))->setName('PHP'));

        $page->setTerms($terms);

        self::assertSame($terms, $page->getTerms());
    }

    public function testSetVariableLastmodMapsToUpdated(): void
    {
        $page = new Page('dates');
        $page->setVariable('lastmod', '2025-01-01');

        self::assertInstanceOf(\DateTime::class, $page->getVariable('updated'));
        self::assertSame('2025-01-01', $page->getVariable('updated')->format('Y-m-d'));
    }

    public function testSetVariableDateRejectsInvalidValue(): void
    {
        $page = new Page('dates');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('is not a valid date');

        $page->setVariable('date', ['not-a-date']);
    }

    public function testParseReadsFrontmatterAndBody(): void
    {
        $file = $this->createFileInfo('', 'content.md', "---\ntitle: Parsed\n---\nHello body");
        $page = new Page($file);

        $page->parse();

        self::assertSame('title: Parsed', $page->getFrontmatter());
        self::assertSame('Hello body', $page->getBody());
    }

    public function testSetSlugUpdatesPathWhenSlugAlreadySet(): void
    {
        $page = new Page('slug');
        $page->setFolder('blog');
        $page->setSlug('first');
        $page->setSlug('second');

        self::assertSame('blog/second', $page->getPath());
        self::assertSame('second', $page->getSlug());
    }

    public function testSetPathWithoutSlashUpdatesSlug(): void
    {
        $page = new Page('single');
        $page->setPath('landing');

        self::assertSame('landing', $page->getSlug());
    }

    private function createFileInfo(string $relativePath, string $filename, string $content): SplFileInfo
    {
        $dir = $relativePath === ''
            ? $this->tmpDir
            : $this->tmpDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        $this->filesystem->mkdir($dir);

        $filePath = $dir . DIRECTORY_SEPARATOR . $filename;
        file_put_contents($filePath, $content);

        $relativePathname = $relativePath === '' ? $filename : $relativePath . '/' . $filename;

        return new SplFileInfo($filePath, $relativePath, $relativePathname);
    }
}

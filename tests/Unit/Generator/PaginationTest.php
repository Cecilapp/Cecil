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
     */
    private function page(string $relativePath, string $filename, array $variables = []): Page
    {
        $dir = $this->tmpDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        $this->filesystem->mkdir($dir);
        $filePath = $dir . DIRECTORY_SEPARATOR . $filename;
        file_put_contents($filePath, "---\ntitle: Test\n---\nBody");
        $file = new SplFileInfo($filePath, $relativePath, $relativePath . '/' . $filename);

        $page = (new Page($file))->parse()->setVariables($variables);
        $page->setVariable('language', $this->builder->getConfig()->getLanguageDefault());

        return $page;
    }
}

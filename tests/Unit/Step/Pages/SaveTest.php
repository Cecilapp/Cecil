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

namespace Cecil\Test\Unit\Step\Pages;

use Cecil\Builder;
use Cecil\Collection\Page\Collection as PagesCollection;
use Cecil\Collection\Page\Page;
use Cecil\Exception\RuntimeException;
use Cecil\Logger\PrintLogger;
use Cecil\Step\Pages\Save;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class SaveTest extends TestCase
{
    private string $root;

    private string $siteDir;

    private Filesystem $filesystem;

    private Builder $builder;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cecil-save-pages-test-' . uniqid('', true);
        $this->siteDir = $this->root . DIRECTORY_SEPARATOR . 'site';
        $this->filesystem->mkdir($this->siteDir);

        $this->builder = new Builder([], new PrintLogger(Builder::VERBOSITY_QUIET));
        $this->builder->setSourceDir($this->siteDir);
        $this->builder->setDestinationDir($this->siteDir);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->root);
    }

    public function testPageIsSavedInTheOutputDirectory(): void
    {
        $this->save('blog/post');

        self::assertFileExists($this->builder->getConfig()->getOutputPath() . '/blog/post/index.html');
    }

    public function testPageOutsideTheOutputDirectoryIsNotSaved(): void
    {
        try {
            $this->save('../../escaped');
            self::fail('A RuntimeException should be thrown.');
        } catch (RuntimeException $e) {
            self::assertStringContainsString('outside the output directory', $e->getMessage());
        }

        self::assertFileDoesNotExist($this->root . '/escaped/index.html');
    }

    private function save(string $path): void
    {
        // the path is forced (as a generator could do), bypassing the front matter validation
        $page = (new Page('page'))->setPath($path);
        $page->addRendered(['html' => ['output' => '<p>Hello</p>', 'template' => []]]);
        $this->builder->setPages(new PagesCollection('all-pages', [$page]));

        $step = new Save($this->builder);
        $step->init([]);
        $step->process();
    }
}

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
use Cecil\Generator\Alias;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\SplFileInfo;

class AliasTest extends TestCase
{
    private string $tmpDir;

    private Filesystem $filesystem;

    private Builder $builder;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cecil-alias-test-' . uniqid('', true);
        $this->filesystem->mkdir($this->tmpDir);
        $this->builder = new Builder();
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->tmpDir);
    }

    public function testAliasPageIsCreated(): void
    {
        $this->builder->setPages(new PagesCollection('all-pages', [
            $this->page('blog', 'post.md', ['alias' => 'blog/old-post']),
        ]));

        $generated = (new Alias($this->builder))->runGenerate();

        self::assertTrue($generated->has('blog/old-post'));
        $alias = $generated->get('blog/old-post');
        self::assertSame('blog/old-post', $alias->getPath());
        self::assertSame('redirect', $alias->getVariable('layout'));
        self::assertSame('blog/post', $alias->getVariable('redirect')->getId());
    }

    public function testAliasOfTheOriginalPathDoesNotOverrideAPageWithACustomPath(): void
    {
        // the page ID ("fr/blog/post") is built from its file, while it is published at its custom path
        $this->builder->setPages(new PagesCollection('all-pages', [
            $this->page('blog', 'post.md'),
            $this->page('blog', 'post.fr.md', ['path' => 'blog/article', 'alias' => 'blog/post']),
        ]));

        $generated = (new Alias($this->builder))->runGenerate();

        // the alias page has a distinct ID, so the page is not replaced by its redirection
        self::assertFalse($generated->has('fr/blog/post'));
        self::assertTrue($generated->has('fr/blog/post#alias'));
        $alias = $generated->get('fr/blog/post#alias');
        self::assertSame('blog/post', $alias->getPath());
        self::assertSame('fr', $alias->getVariable('language'));
        self::assertSame('fr/blog/post', $alias->getVariable('redirect')->getId());
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
        // the language is set before generators run (default language if no suffix)
        if ($page->getVariable('language') === null) {
            $page->setVariable('language', $this->builder->getConfig()->getLanguageDefault());
        }

        return $page;
    }
}

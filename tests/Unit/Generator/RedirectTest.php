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
use Cecil\Generator\Redirect;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\SplFileInfo;

class RedirectTest extends TestCase
{
    private string $tmpDir;

    private Filesystem $filesystem;

    private Builder $builder;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cecil-redirect-test-' . uniqid('', true);
        $this->filesystem->mkdir($this->tmpDir);
        $this->builder = new Builder();
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->tmpDir);
    }

    public function testRedirectPageGetsRedirectLayout(): void
    {
        $this->builder->setPages(new PagesCollection('all-pages', [
            $this->page('hosting/netlify', 'deploy.md', ['redirect' => 'https://example.com']),
            $this->page('blog', 'post.md'),
        ]));

        $generated = (new Redirect($this->builder))->runGenerate();

        self::assertCount(1, $generated);
        self::assertTrue($generated->has('hosting/netlify/deploy'));
        self::assertSame('redirect', $generated->get('hosting/netlify/deploy')->getVariable('layout'));
    }

    public function testRedirectPageIsUpdatedInPlace(): void
    {
        // a section holds a reference to the page before the generator runs
        $page = $this->page('hosting/netlify', 'deploy.md', ['redirect' => 'https://example.com']);
        $this->builder->setPages(new PagesCollection('all-pages', [$page]));
        $sectionPages = new PagesCollection('section-hosting', [$page]);

        $generated = (new Redirect($this->builder))->runGenerate();

        // the generated page is the same instance as the one referenced by the section,
        // so variables set later (e.g.: "output" while rendering) are shared
        self::assertSame($page, $generated->get('hosting/netlify/deploy'));
        self::assertSame($generated->get('hosting/netlify/deploy'), $sectionPages->get('hosting/netlify/deploy'));
    }

    public function testPageWithRedirectLayoutIsIgnored(): void
    {
        $this->builder->setPages(new PagesCollection('all-pages', [
            $this->page('blog', 'old-post.md', ['redirect' => 'blog/post', 'layout' => 'redirect']),
        ]));

        $generated = (new Redirect($this->builder))->runGenerate();

        self::assertCount(0, $generated);
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
